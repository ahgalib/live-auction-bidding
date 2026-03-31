<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\User;
use App\Services\AuctionBiddingService;
use App\Services\OAuthTokenService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        Log::info('graphql.request', [
            'operation' => $operation,
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return match ($operation) {
            'Auctions' => $this->auctionsQuery(),
            'AuctionState' => $this->auctionStateQuery($variables),
            'AdminAuctions' => $this->adminAuctionsQuery($request),
            'Register' => $this->registerMutation($variables),
            'Login' => $this->loginMutation($variables, $tokenService),
            'OAuthToken' => $this->oauthTokenMutation($variables, $tokenService),
            'Me' => $this->meQuery($request),
            'Logout' => $this->logoutMutation($request, $tokenService),
            'PlaceBid' => $this->placeBidMutation($request, $variables, $biddingService),
            'WithdrawBid' => $this->withdrawBidMutation($request, $variables, $biddingService),
            'CreateAuction' => $this->createAuctionMutation($request, $variables),
            'UpdateAuction' => $this->updateAuctionMutation($request, $variables),
            'AdjustAuctionTime' => $this->adjustAuctionTimeMutation($request, $variables),
            'DeleteAuction' => $this->deleteAuctionMutation($request, $variables),
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
        $auction = $this->closeIfExpired($auction);

        return $this->graphqlData([
            'auction' => [
                'id' => (string) $auction->id,
                'title' => $auction->title,
                'description' => $auction->description,
                'startingPrice' => (float) $auction->starting_price,
                'currentPrice' => (float) $auction->current_price,
                'minIncrement' => (float) $auction->min_increment,
                'currentWinnerId' => $auction->current_winner_id ? (string) $auction->current_winner_id : null,
                'winnerName' => $auction->currentWinner?->name,
                'endTime' => $auction->end_time?->toIso8601String(),
                'category' => $auction->category,
                'status' => $auction->status,
                'participantCount' => $this->participantCount($auction->id),
                'bids' => $this->recentBids($auction->id),
            ],
        ]);
    }

    private function auctionsQuery(): JsonResponse
    {
        Auction::query()
            ->where('status', 'active')
            ->where('end_time', '<=', now())
            ->update(['status' => 'closed']);

        $auctions = Auction::query()
            ->where('status', 'active')
            ->orderBy('end_time')
            ->get()
            ->map(fn (Auction $auction): array => [
                'id' => (string) $auction->id,
                'title' => $auction->title,
                'description' => $auction->description,
                'startingPrice' => (float) $auction->starting_price,
                'currentPrice' => (float) $auction->current_price,
                'minIncrement' => (float) $auction->min_increment,
                'currentWinnerId' => $auction->current_winner_id ? (string) $auction->current_winner_id : null,
                'winnerName' => $auction->currentWinner?->name,
                'endTime' => $auction->end_time?->toIso8601String(),
                'category' => $auction->category,
                'status' => $auction->status,
                'participantCount' => $this->participantCount($auction->id),
            ])
            ->values()
            ->all();

        return $this->graphqlData([
            'auctions' => $auctions,
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
                'walletBalance' => (float) $user->wallet_balance,
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

    private function adminAuctionsQuery(Request $request): JsonResponse
    {
        $admin = $this->resolveBearerUser($request);
        if (! $admin) {
            return $this->graphqlError('Authentication required.', 'UNAUTHENTICATED');
        }

        if (! $admin->is_admin) {
            return $this->graphqlError('Forbidden.', 'FORBIDDEN');
        }

        $auctions = Auction::query()
            ->orderByDesc('id')
            ->get()
            ->map(function (Auction $auction): array {
                $auction = $this->closeIfExpired($auction);

                return [
                'id' => (string) $auction->id,
                'title' => $auction->title,
                'description' => $auction->description,
                'startingPrice' => (float) $auction->starting_price,
                'currentPrice' => (float) $auction->current_price,
                'minIncrement' => (float) $auction->min_increment,
                'currentWinnerId' => $auction->current_winner_id ? (string) $auction->current_winner_id : null,
                'winnerName' => $auction->currentWinner?->name,
                'endTime' => $auction->end_time?->toIso8601String(),
                'category' => $auction->category,
                'status' => $auction->status,
                'participantCount' => $this->participantCount($auction->id),
            ];
            })
            ->values()
            ->all();

        return $this->graphqlData([
            'adminAuctions' => $auctions,
        ]);
    }

    private function createAuctionMutation(Request $request, array $variables): JsonResponse
    {
        $admin = $this->resolveBearerUser($request);
        if (! $admin) {
            return $this->graphqlError('Authentication required.', 'UNAUTHENTICATED');
        }

        if (! $admin->is_admin) {
            return $this->graphqlError('Forbidden.', 'FORBIDDEN');
        }

        $validator = Validator::make($variables, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'startingPrice' => ['required', 'numeric', 'gt:0'],
            'minIncrement' => ['required', 'numeric', 'gt:0'],
            'endTime' => ['required', 'date'],
            'category' => ['required', 'string', 'max:120'],
            'status' => ['nullable', 'in:draft,active,pending_payment,closed'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        $endTimeUtc = CarbonImmutable::parse((string) $variables['endTime'])->setTimezone('UTC');
        $startingPrice = (float) $variables['startingPrice'];

        $auction = Auction::query()->create([
            'seller_id' => $admin->id,
            'title' => (string) $variables['title'],
            'description' => isset($variables['description']) ? (string) $variables['description'] : null,
            'starting_price' => $startingPrice,
            'min_increment' => (float) $variables['minIncrement'],
            'current_price' => $startingPrice,
            'current_winner_id' => null,
            'end_time' => $endTimeUtc,
            'category' => (string) $variables['category'],
            'status' => (string) ($variables['status'] ?? 'draft'),
        ]);

        return $this->graphqlData([
            'createAuction' => [
                'id' => (string) $auction->id,
                'title' => $auction->title,
                'description' => $auction->description,
                'startingPrice' => (float) $auction->starting_price,
                'currentPrice' => (float) $auction->current_price,
                'minIncrement' => (float) $auction->min_increment,
                'currentWinnerId' => $auction->current_winner_id ? (string) $auction->current_winner_id : null,
                'winnerName' => $auction->currentWinner?->name,
                'endTime' => $auction->end_time?->toIso8601String(),
                'category' => $auction->category,
                'status' => $auction->status,
                'participantCount' => $this->participantCount($auction->id),
            ],
        ]);
    }

    private function updateAuctionMutation(Request $request, array $variables): JsonResponse
    {
        $admin = $this->resolveBearerUser($request);
        if (! $admin) {
            return $this->graphqlError('Authentication required.', 'UNAUTHENTICATED');
        }

        if (! $admin->is_admin) {
            return $this->graphqlError('Forbidden.', 'FORBIDDEN');
        }

        $validator = Validator::make($variables, [
            'id' => ['required', 'integer', 'min:1'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'startingPrice' => ['sometimes', 'numeric', 'gt:0'],
            'minIncrement' => ['sometimes', 'numeric', 'gt:0'],
            'currentPrice' => ['sometimes', 'numeric', 'gt:0'],
            'endTime' => ['sometimes', 'date'],
            'category' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', 'in:draft,active,pending_payment,closed'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        $auction = Auction::query()->find((int) $variables['id']);
        if (! $auction) {
            return $this->graphqlError('Auction not found.', 'NOT_FOUND');
        }

        $updates = [];
        if (array_key_exists('title', $variables)) {
            $updates['title'] = (string) $variables['title'];
        }
        if (array_key_exists('description', $variables)) {
            $updates['description'] = $variables['description'] !== null ? (string) $variables['description'] : null;
        }
        if (array_key_exists('startingPrice', $variables)) {
            $updates['starting_price'] = (float) $variables['startingPrice'];
        }
        if (array_key_exists('minIncrement', $variables)) {
            $updates['min_increment'] = (float) $variables['minIncrement'];
        }
        if (array_key_exists('currentPrice', $variables)) {
            $updates['current_price'] = (float) $variables['currentPrice'];
        }
        if (array_key_exists('endTime', $variables)) {
            $updates['end_time'] = CarbonImmutable::parse((string) $variables['endTime'])->setTimezone('UTC');
        }
        if (array_key_exists('category', $variables)) {
            $updates['category'] = (string) $variables['category'];
        }
        if (array_key_exists('status', $variables)) {
            $updates['status'] = (string) $variables['status'];
        }

        if ($updates !== []) {
            $auction->forceFill($updates)->save();
            if (array_key_exists('end_time', $updates)) {
                $this->syncAuctionTimeCache($auction);
            }
        }

        return $this->graphqlData([
            'updateAuction' => [
                'id' => (string) $auction->id,
                'title' => $auction->title,
                'description' => $auction->description,
                'startingPrice' => (float) $auction->starting_price,
                'currentPrice' => (float) $auction->current_price,
                'minIncrement' => (float) $auction->min_increment,
                'currentWinnerId' => $auction->current_winner_id ? (string) $auction->current_winner_id : null,
                'winnerName' => $auction->currentWinner?->name,
                'endTime' => $auction->end_time?->toIso8601String(),
                'category' => $auction->category,
                'status' => $auction->status,
                'participantCount' => $this->participantCount($auction->id),
            ],
        ]);
    }

    private function adjustAuctionTimeMutation(Request $request, array $variables): JsonResponse
    {
        $admin = $this->resolveBearerUser($request);
        if (! $admin) {
            return $this->graphqlError('Authentication required.', 'UNAUTHENTICATED');
        }

        if (! $admin->is_admin) {
            return $this->graphqlError('Forbidden.', 'FORBIDDEN');
        }

        $validator = Validator::make($variables, [
            'id' => ['required', 'integer', 'min:1'],
            'deltaMinutes' => ['required', 'integer', 'between:-180,180', 'not_in:0'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        $auction = Auction::query()->find((int) $variables['id']);
        if (! $auction) {
            return $this->graphqlError('Auction not found.', 'NOT_FOUND');
        }
        if ($auction->status !== 'active') {
            return $this->graphqlError('Only active auctions can be time-adjusted.', 'BAD_REQUEST');
        }

        $deltaMinutes = (int) $variables['deltaMinutes'];
        $currentEndTime = CarbonImmutable::parse($auction->end_time)->setTimezone('UTC');
        $newEndTime = $currentEndTime->addMinutes($deltaMinutes);

        if ($newEndTime->lessThan(now()->utc()->addSeconds(10))) {
            $newEndTime = now()->utc()->addSeconds(10)->toImmutable();
        }

        $auction->forceFill([
            'end_time' => $newEndTime,
        ])->save();
        $this->syncAuctionTimeCache($auction);

        return $this->graphqlData([
            'adjustAuctionTime' => [
                'id' => (string) $auction->id,
                'endTime' => $newEndTime->toIso8601String(),
                'status' => $auction->status,
            ],
        ]);
    }

    private function deleteAuctionMutation(Request $request, array $variables): JsonResponse
    {
        $admin = $this->resolveBearerUser($request);
        if (! $admin) {
            return $this->graphqlError('Authentication required.', 'UNAUTHENTICATED');
        }

        if (! $admin->is_admin) {
            return $this->graphqlError('Forbidden.', 'FORBIDDEN');
        }

        $validator = Validator::make($variables, [
            'id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        $auction = Auction::query()->find((int) $variables['id']);
        if (! $auction) {
            return $this->graphqlError('Auction not found.', 'NOT_FOUND');
        }

        $auction->delete();

        return $this->graphqlData([
            'deleteAuction' => true,
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

    private function participantCount(int $auctionId): int
    {
        return DB::table('bid_logs')
            ->where('auction_id', $auctionId)
            ->where('event_type', 'accepted')
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentBids(int $auctionId, int $limit = 50): array
    {
        return DB::table('bid_logs')
            ->leftJoin('users', 'users.id', '=', 'bid_logs.user_id')
            ->where('bid_logs.auction_id', $auctionId)
            ->whereIn('bid_logs.event_type', ['accepted', 'withdrawn'])
            ->orderByDesc('bid_logs.id')
            ->limit($limit)
            ->get([
                'bid_logs.id',
                'bid_logs.amount',
                'bid_logs.event_type',
                'bid_logs.created_at',
                'users.name as bidder_name',
            ])
            ->map(function ($row): array {
                return [
                    'id' => (string) $row->id,
                    'amount' => (float) $row->amount,
                    'status' => (string) $row->event_type,
                    'bidderName' => $row->bidder_name,
                    'createdAt' => CarbonImmutable::parse($row->created_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    private function syncAuctionTimeCache(Auction $auction): void
    {
        Cache::put("auction:{$auction->id}:end_time", CarbonImmutable::parse($auction->end_time)->toIso8601String());
    }

    private function closeIfExpired(Auction $auction): Auction
    {
        if ($auction->status === 'active' && CarbonImmutable::parse($auction->end_time)->isPast()) {
            $auction->forceFill([
                'status' => 'closed',
            ])->save();
        }

        return $auction->fresh(['currentWinner']);
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
