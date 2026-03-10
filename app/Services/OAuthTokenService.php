<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OAuthTokenService
{
    /**
     * @param array<int, string> $scopes
     * @return array<string, mixed>
     */
    public function issuePasswordGrantToken(User $user, int $clientId, array $scopes = []): array
    {
        $plainToken = Str::random(80);
        $tokenId = hash('sha256', $plainToken);
        $expiresAt = now()->addHours((int) env('OAUTH_ACCESS_TOKEN_TTL_HOURS', 2));

        DB::table('oauth_access_tokens')->insert([
            'id' => $tokenId,
            'user_id' => $user->id,
            'client_id' => $clientId,
            'name' => 'password-grant',
            'scopes' => json_encode(array_values($scopes), JSON_THROW_ON_ERROR),
            'revoked' => false,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'access_token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_in' => now()->diffInSeconds($expiresAt),
        ];
    }

    public function revokeToken(string $plainToken): void
    {
        $tokenId = hash('sha256', $plainToken);

        DB::table('oauth_access_tokens')
            ->where('id', $tokenId)
            ->update([
                'revoked' => true,
                'updated_at' => now(),
            ]);
    }
}
