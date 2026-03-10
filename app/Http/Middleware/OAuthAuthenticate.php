<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OAuthAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();
        if (! $bearerToken) {
            return $this->unauthorized('Missing bearer token');
        }

        $tokenId = hash('sha256', $bearerToken);
        $record = DB::table('oauth_access_tokens')
            ->where('id', $tokenId)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return $this->unauthorized('Invalid or expired token');
        }

        $user = User::query()->find($record->user_id);
        if (! $user) {
            return $this->unauthorized('User not found');
        }

        $request->setUserResolver(static fn (): User => $user);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }
}
