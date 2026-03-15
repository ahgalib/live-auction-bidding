<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\User;
use App\Services\AuctionBiddingService;
use App\Services\OAuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class GraphqlController extends Controller
{
    public function __invoke(Request $request, AuctionBiddingService $biddingService, OAuthTokenService $tokenService): JsonResponse
    {
        $query = (string) $request->input('query', '');
        $variables = (array) $request->input('variables', []);
        $operationName = $request->input('operationName');

        if ($query === '') {
            return $this->graphqlError('Query is required.', 'BAD_REQUEST');
        }

        $operation = is_string($operationName) && $operationName !== ''
            ? $operationName
            : $this->extractOperationName($query);

        return match ($operation) {
            'AuctionState' => $this->auctionStateQuery($variables),
            'ServerTime' => $this->serverTimeQuery(),
            'Register' => $this->registerMutation($variables),
            'Login' => $this->loginMutation($variables, $tokenService),
            'OAuthToken' => $this->oauthTokenMutation($variables, $tokenService),
            'Me' => $this->meQuery($request),
            'Logout' => $this->logoutMutation($request, $tokenService),
            'PlaceBid' => $this->placeBidMutation($request, $variables, $biddingService),
            'WithdrawBid' => $this->withdrawBidMutation($request, $variables, $biddingService),
            default => $this->graphqlError('Unknown operation. Use a named GraphQL operation.', 'UNKNOWN_OPERATION'),
        };
    }

    private function extractOperationName(string $query): ?string
    {
        if (preg_match('/\b(?:query|mutation)\s+([_A-Za-z][_0-9A-Za-z]*)/', $query, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function auctionStateQuery(array $variables): JsonResponse
    {
        $validator = Validator::make($variables, [
            'id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        $auction = Auction::query()->find($variables['id']);
        if (! $auction) {
            return $this->graphqlError('Auction not found.', 'NOT_FOUND');
        }

        return $this->graphqlData([
            'auction' => [
                'id' => (string) $auction->id,
                'currentPrice' => (float) $auction->current_price,
                'currentWinnerId' => $auction->current_winner_id ? (string) $auction->current_winner_id : null,
                'endTime' => $auction->end_time?->toIso8601String(),
                'status' => $auction->status,
            ],
        ]);
    }

    private function serverTimeQuery(): JsonResponse
    {
        return $this->graphqlData([
            'serverTime' => [
                'serverTimeUtc' => now()->toIso8601String(),
            ],
        ]);
    }

    private function registerMutation(array $variables): JsonResponse
    {
        $validator = Validator::make($variables, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        $user = User::query()->create([
            'name' => (string) $variables['name'],
            'email' => (string) $variables['email'],
            'password' => (string) $variables['password'],
        ]);

        return $this->graphqlData([
            'register' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    private function loginMutation(array $variables, OAuthTokenService $tokenService): JsonResponse
    {
        $validator = Validator::make($variables, [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        $user = User::query()->where('email', $variables['email'])->first();
        if (! $user || ! Hash::check((string) $variables['password'], $user->password)) {
            return $this->graphqlError('Invalid credentials.', 'UNAUTHENTICATED');
        }

        $token = $tokenService->issuePasswordGrantToken($user, 1, ['bid:write', 'auction:read']);

        return $this->graphqlData([
            'login' => $token,
        ]);
    }

    private function oauthTokenMutation(array $variables, OAuthTokenService $tokenService): JsonResponse
    {
        $validator = Validator::make($variables, [
            'grantType' => ['required', 'in:password'],
            'clientId' => ['required', 'integer'],
            'clientSecret' => ['required', 'string'],
            'username' => ['required', 'email'],
            'password' => ['required', 'string'],
            'scope' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        $client = DB::table('oauth_clients')
            ->where('id', (int) $variables['clientId'])
            ->where('revoked', false)
            ->where('password_client', true)
            ->first();

        if (! $client || ! hash_equals((string) $client->secret, (string) $variables['clientSecret'])) {
            return $this->graphqlError('Client authentication failed.', 'UNAUTHENTICATED');
        }

        $user = User::query()->where('email', $variables['username'])->first();
        if (! $user || ! Hash::check((string) $variables['password'], $user->password)) {
            return $this->graphqlError('The user credentials were incorrect.', 'UNAUTHENTICATED');
        }

        $scopeInput = $variables['scope'] ?? null;
        $scopes = is_string($scopeInput) && $scopeInput !== '' ? preg_split('/\s+/', trim($scopeInput)) : [];
        $token = $tokenService->issuePasswordGrantToken($user, (int) $client->id, $scopes ?: ['default']);

        return $this->graphqlData([
            'oauthToken' => $token,
        ]);
    }

    private function meQuery(Request $request): JsonResponse
    {
        $user = $this->resolveBearerUser($request);
        if (! $user) {
            return $this->graphqlError('Authentication required.', 'UNAUTHENTICATED');
        }

        return $this->graphqlData([
            'me' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'isAdmin' => (bool) $user->is_admin,
            ],
        ]);
    }

    private function logoutMutation(Request $request, OAuthTokenService $tokenService): JsonResponse
    {
        $bearerToken = $request->bearerToken();
        if (! $bearerToken) {
            return $this->graphqlError('Authentication required.', 'UNAUTHENTICATED');
        }

        $tokenService->revokeToken($bearerToken);

        return $this->graphqlData([
            'logout' => true,
        ]);
    }

    private function placeBidMutation(Request $request, array $variables, AuctionBiddingService $biddingService): JsonResponse
    {
        $user = $this->resolveBearerUser($request);
        if (! $user) {
            return $this->graphqlError('Authentication required.', 'UNAUTHENTICATED');
        }

        $validator = Validator::make($variables, [
            'auctionId' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'requestId' => ['nullable', 'string', 'max:64'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        $auction = Auction::query()->find($variables['auctionId']);
        if (! $auction) {
            return $this->graphqlError('Auction not found.', 'NOT_FOUND');
        }

        $result = $biddingService->placeBid(
            $auction,
            $user,
            (float) $variables['amount'],
            $request->ip(),
            isset($variables['requestId']) ? (string) $variables['requestId'] : null,
        );

        return $this->graphqlData([
            'placeBid' => [
                'accepted' => (bool) $result['accepted'],
                'currentPrice' => (float) $result['current_price'],
                'endTime' => (string) $result['end_time'],
                'errorCode' => $result['error_code'],
            ],
        ]);
    }

    private function withdrawBidMutation(Request $request, array $variables, AuctionBiddingService $biddingService): JsonResponse
    {
        $user = $this->resolveBearerUser($request);
        if (! $user) {
            return $this->graphqlError('Authentication required.', 'UNAUTHENTICATED');
        }

        $validator = Validator::make($variables, [
            'auctionId' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        $auction = Auction::query()->find($variables['auctionId']);
        if (! $auction) {
            return $this->graphqlError('Auction not found.', 'NOT_FOUND');
        }

        try {
            $result = $biddingService->withdrawLatestBid($auction, $user, $variables['reason'] ?? null);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return $this->graphqlError('Forbidden.', 'FORBIDDEN');
            }

            if ($e->getMessage() === 'NO_BID_TO_WITHDRAW') {
                return $this->graphqlError('No bid to withdraw.', 'BAD_REQUEST');
            }

            throw $e;
        }

        return $this->graphqlData([
            'withdrawBid' => [
                'success' => (bool) $result['success'],
                'restoredPrice' => (float) $result['restored_price'],
                'restoredWinnerId' => $result['restored_winner_id'] ? (string) $result['restored_winner_id'] : null,
            ],
        ]);
    }

    private function resolveBearerUser(Request $request): ?User
    {
        $bearerToken = $request->bearerToken();
        if (! $bearerToken) {
            return null;
        }

        $record = DB::table('oauth_access_tokens')
            ->where('id', hash('sha256', $bearerToken))
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return null;
        }

        return User::query()->find($record->user_id);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function graphqlData(array $data): JsonResponse
    {
        return response()->json([
            'data' => $data,
        ]);
    }

    private function validationError(string $message): JsonResponse
    {
        return $this->graphqlError($message, 'BAD_USER_INPUT');
    }

    private function graphqlError(string $message, string $code): JsonResponse
    {
        return response()->json([
            'data' => null,
            'errors' => [
                [
                    'message' => $message,
                    'extensions' => [
                        'code' => $code,
                    ],
                ],
            ],
        ], 200);
    }
}

