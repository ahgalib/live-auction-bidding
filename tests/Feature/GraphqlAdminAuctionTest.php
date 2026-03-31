<?php

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GraphqlAdminAuctionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_create_auctions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $seller = User::factory()->create();

        Auction::query()->create([
            'seller_id' => $seller->id,
            'title' => 'Existing Auction',
            'starting_price' => 100,
            'min_increment' => 5,
            'current_price' => 100,
            'end_time' => CarbonImmutable::now()->addHour(),
            'category' => 'General',
            'status' => 'active',
        ]);

        $token = $this->issueTokenFor($admin);

        $listResponse = $this->withToken($token)->postJson('/graphql', [
            'query' => 'query AdminAuctions { adminAuctions { id title status } }',
            'operationName' => 'AdminAuctions',
        ]);

        $listResponse->assertOk()
            ->assertJsonPath('data.adminAuctions.0.title', 'Existing Auction');

        $createResponse = $this->withToken($token)->postJson('/graphql', [
            'query' => 'mutation CreateAuction($title: String!, $startingPrice: Float!, $minIncrement: Float!, $endTime: String!, $category: String!, $status: String) { createAuction(title: $title, startingPrice: $startingPrice, minIncrement: $minIncrement, endTime: $endTime, category: $category, status: $status) { id title } }',
            'operationName' => 'CreateAuction',
            'variables' => [
                'title' => 'Admin Created Auction',
                'startingPrice' => 200,
                'minIncrement' => 10,
                'endTime' => CarbonImmutable::now()->addMinutes(90)->toIso8601String(),
                'category' => 'Electronics',
                'status' => 'draft',
            ],
        ]);

        $createResponse->assertOk()
            ->assertJsonPath('data.createAuction.title', 'Admin Created Auction');

        $this->assertDatabaseHas('auctions', [
            'title' => 'Admin Created Auction',
            'status' => 'draft',
        ]);
    }

    public function test_non_admin_cannot_access_admin_operations(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $token = $this->issueTokenFor($user);

        $listResponse = $this->withToken($token)->postJson('/graphql', [
            'query' => 'query AdminAuctions { adminAuctions { id } }',
            'operationName' => 'AdminAuctions',
        ]);

        $listResponse->assertOk()
            ->assertJsonPath('errors.0.extensions.code', 'FORBIDDEN');

        $createResponse = $this->withToken($token)->postJson('/graphql', [
            'query' => 'mutation CreateAuction($title: String!, $startingPrice: Float!, $minIncrement: Float!, $endTime: String!, $category: String!, $status: String) { createAuction(title: $title, startingPrice: $startingPrice, minIncrement: $minIncrement, endTime: $endTime, category: $category, status: $status) { id } }',
            'operationName' => 'CreateAuction',
            'variables' => [
                'title' => 'Blocked Auction',
                'startingPrice' => 100,
                'minIncrement' => 5,
                'endTime' => CarbonImmutable::now()->addHour()->toIso8601String(),
                'category' => 'General',
                'status' => 'draft',
            ],
        ]);

        $createResponse->assertOk()
            ->assertJsonPath('errors.0.extensions.code', 'FORBIDDEN');
    }

    public function test_auctions_query_returns_active_auctions_with_participant_count(): void
    {
        $seller = User::factory()->create();
        $bidderA = User::factory()->create();
        $bidderB = User::factory()->create();

        $auction = Auction::query()->create([
            'seller_id' => $seller->id,
            'title' => 'Realtime Auction',
            'description' => 'Purpose text',
            'starting_price' => 200,
            'min_increment' => 10,
            'current_price' => 200,
            'end_time' => CarbonImmutable::now()->addHour(),
            'category' => 'Electronics',
            'status' => 'active',
        ]);

        DB::table('bid_logs')->insert([
            [
                'auction_id' => $auction->id,
                'user_id' => $bidderA->id,
                'amount' => 220,
                'event_type' => 'accepted',
                'request_id' => null,
                'ip_address' => null,
                'meta' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ],
            [
                'auction_id' => $auction->id,
                'user_id' => $bidderB->id,
                'amount' => 240,
                'event_type' => 'accepted',
                'request_id' => null,
                'ip_address' => null,
                'meta' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ],
        ]);

        $response = $this->postJson('/graphql', [
            'query' => 'query Auctions { auctions { id title description participantCount status } }',
            'operationName' => 'Auctions',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.auctions.0.title', 'Realtime Auction')
            ->assertJsonPath('data.auctions.0.description', 'Purpose text')
            ->assertJsonPath('data.auctions.0.participantCount', 2);
    }

    public function test_admin_can_adjust_auction_time(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $seller = User::factory()->create();

        $auction = Auction::query()->create([
            'seller_id' => $seller->id,
            'title' => 'Adjustable Auction',
            'description' => 'Adjust test',
            'starting_price' => 100,
            'min_increment' => 5,
            'current_price' => 100,
            'end_time' => CarbonImmutable::now()->addMinutes(30),
            'category' => 'General',
            'status' => 'active',
        ]);

        $oldEnd = CarbonImmutable::parse($auction->end_time);

        $response = $this->withToken($this->issueTokenFor($admin))->postJson('/graphql', [
            'query' => 'mutation AdjustAuctionTime($id: ID!, $deltaMinutes: Int!) { adjustAuctionTime(id: $id, deltaMinutes: $deltaMinutes) { id endTime status } }',
            'operationName' => 'AdjustAuctionTime',
            'variables' => [
                'id' => $auction->id,
                'deltaMinutes' => 15,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.adjustAuctionTime.id', (string) $auction->id);

        $auction->refresh();
        $this->assertGreaterThan($oldEnd->timestamp, CarbonImmutable::parse($auction->end_time)->timestamp);
    }

    private function issueTokenFor(User $user): string
    {
        $plainToken = Str::random(80);

        DB::table('oauth_access_tokens')->insert([
            'id' => hash('sha256', $plainToken),
            'user_id' => $user->id,
            'client_id' => 1,
            'name' => 'test-token',
            'scopes' => json_encode(['auction:manage'], JSON_THROW_ON_ERROR),
            'revoked' => false,
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $plainToken;
    }
}
