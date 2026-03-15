<?php

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GraphqlEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_time_query_returns_graphql_data_shape(): void
    {
        $response = $this->postJson('/graphql', [
            'query' => 'query ServerTime { serverTime { serverTimeUtc } }',
            'operationName' => 'ServerTime',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.serverTime.serverTimeUtc', fn ($value) => is_string($value) && $value !== '')
            ->assertJsonMissingPath('errors.0.message');
    }

    public function test_place_bid_mutation_accepts_higher_bid(): void
    {
        $seller = User::factory()->create();
        $bidder = User::factory()->create();
        $auction = Auction::query()->create([
            'seller_id' => $seller->id,
            'starting_price' => 100,
            'current_price' => 100,
            'end_time' => CarbonImmutable::now()->addMinutes(5),
            'status' => 'active',
        ]);

        $token = $this->issueTokenFor($bidder);

        $response = $this->withToken($token)->postJson('/graphql', [
            'query' => 'mutation PlaceBid($auctionId: ID!, $amount: Float!) { placeBid(auctionId: $auctionId, amount: $amount) { accepted currentPrice endTime errorCode } }',
            'operationName' => 'PlaceBid',
            'variables' => [
                'auctionId' => $auction->id,
                'amount' => 120,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.placeBid.accepted', true)
            ->assertJsonPath('data.placeBid.currentPrice', 120)
            ->assertJsonPath('data.placeBid.errorCode', null);
    }

    private function issueTokenFor(User $user): string
    {
        $plainToken = Str::random(80);

        DB::table('oauth_access_tokens')->insert([
            'id' => hash('sha256', $plainToken),
            'user_id' => $user->id,
            'client_id' => 1,
            'name' => 'test-token',
            'scopes' => json_encode(['bid:write'], JSON_THROW_ON_ERROR),
            'revoked' => false,
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $plainToken;
    }
}
