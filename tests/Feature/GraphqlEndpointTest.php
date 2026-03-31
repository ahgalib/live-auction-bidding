<?php

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\BidLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GraphqlEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_auctions_query_returns_graphql_data_shape(): void
    {
        $seller = User::factory()->create();
        Auction::query()->create([
            'seller_id' => $seller->id,
            'title' => 'Active Auction',
            'description' => 'List test',
            'starting_price' => 100,
            'min_increment' => 5,
            'current_price' => 100,
            'end_time' => CarbonImmutable::now()->addMinutes(5),
            'status' => 'active',
            'category' => 'General',
        ]);

        $response = $this->postJson('/graphql', [
            'query' => 'query Auctions { auctions { id title status } }',
            'operationName' => 'Auctions',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.auctions.0.title', 'Active Auction')
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

    public function test_auction_state_marks_expired_active_auction_as_closed(): void
    {
        $seller = User::factory()->create();
        $winner = User::factory()->create(['name' => 'Winner User']);
        $auction = Auction::query()->create([
            'seller_id' => $seller->id,
            'title' => 'Expired Auction',
            'description' => 'Expired test',
            'starting_price' => 100,
            'min_increment' => 5,
            'current_price' => 140,
            'current_winner_id' => $winner->id,
            'end_time' => CarbonImmutable::now()->subMinute(),
            'status' => 'active',
            'category' => 'General',
        ]);

        $response = $this->postJson('/graphql', [
            'query' => 'query AuctionState($id: ID!) { auction(id: $id) { id status winnerName } }',
            'operationName' => 'AuctionState',
            'variables' => [
                'id' => $auction->id,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.auction.status', 'closed')
            ->assertJsonPath('data.auction.winnerName', 'Winner User');
    }

    public function test_auction_state_includes_recent_bid_feed(): void
    {
        $seller = User::factory()->create();
        $bidder = User::factory()->create(['name' => 'Bidder One']);
        $auction = Auction::query()->create([
            'seller_id' => $seller->id,
            'title' => 'Feed Auction',
            'description' => 'Feed test',
            'starting_price' => 100,
            'min_increment' => 5,
            'current_price' => 120,
            'current_winner_id' => $bidder->id,
            'end_time' => CarbonImmutable::now()->addMinutes(5),
            'status' => 'active',
            'category' => 'General',
        ]);

        BidLog::query()->create([
            'auction_id' => $auction->id,
            'user_id' => $bidder->id,
            'amount' => 120,
            'event_type' => 'accepted',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/graphql', [
            'query' => 'query AuctionState($id: ID!) { auction(id: $id) { id bids { id amount status bidderName createdAt } } }',
            'operationName' => 'AuctionState',
            'variables' => [
                'id' => $auction->id,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.auction.bids.0.amount', 120)
            ->assertJsonPath('data.auction.bids.0.status', 'accepted')
            ->assertJsonPath('data.auction.bids.0.bidderName', 'Bidder One');
    }

    public function test_place_bid_with_same_request_id_is_idempotent(): void
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
        $payload = [
            'query' => 'mutation PlaceBid($auctionId: ID!, $amount: Float!, $requestId: String) { placeBid(auctionId: $auctionId, amount: $amount, requestId: $requestId) { accepted currentPrice endTime errorCode } }',
            'operationName' => 'PlaceBid',
            'variables' => [
                'auctionId' => $auction->id,
                'amount' => 120,
                'requestId' => 'req-fixed-1',
            ],
        ];

        $first = $this->withToken($token)->postJson('/graphql', $payload);
        $second = $this->withToken($token)->postJson('/graphql', $payload);

        $first->assertOk()->assertJsonPath('data.placeBid.accepted', true);
        $second->assertOk()->assertJsonPath('data.placeBid.accepted', true);

        $this->assertEquals(
            1,
            BidLog::query()
                ->where('auction_id', $auction->id)
                ->where('event_type', 'accepted')
                ->where('request_id', 'req-fixed-1')
                ->count()
        );
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
