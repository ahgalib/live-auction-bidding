<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OAuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::query()->create($validated);

        return response()->json([
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function login(Request $request, OAuthTokenService $tokenService): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $token = $tokenService->issuePasswordGrantToken($user, 1, ['bid:write', 'auction:read']);

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ]);
    }

    public function oauthToken(Request $request, OAuthTokenService $tokenService): JsonResponse
    {
        $validated = $request->validate([
            'grant_type' => ['required', 'in:password'],
            'client_id' => ['required', 'integer'],
            'client_secret' => ['required', 'string'],
            'username' => ['required', 'email'],
            'password' => ['required', 'string'],
            'scope' => ['nullable', 'string'],
        ]);

        $client = DB::table('oauth_clients')
            ->where('id', $validated['client_id'])
            ->where('revoked', false)
            ->where('password_client', true)
            ->first();

        if (! $client || ! hash_equals((string) $client->secret, $validated['client_secret'])) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed.',
            ], 401);
        }

        $user = User::query()->where('email', $validated['username'])->first();
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'The user credentials were incorrect.',
            ], 401);
        }

        $scopeInput = $validated['scope'] ?? null;
        $scopes = $scopeInput ? preg_split('/\s+/', trim($scopeInput)) : [];
        $token = $tokenService->issuePasswordGrantToken($user, (int) $client->id, $scopes ?: ['default']);

        return response()->json($token);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
        ]);
    }

    public function logout(Request $request, OAuthTokenService $tokenService): JsonResponse
    {
        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            $tokenService->revokeToken($bearerToken);
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
