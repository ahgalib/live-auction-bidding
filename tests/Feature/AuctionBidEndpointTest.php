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

class AuctionBidEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_accepts_a_higher_bid_and_rejects_equal_bid(): void
    {
        $seller = User::factory()->create();
        $bidder = User::factory()->create();
        $auction = $this->createActiveAuction($seller, 450, CarbonImmutable::now()->addMinutes(5));

        $bidderToken = $this->issueTokenFor($bidder);

        $accept = $this->withToken($bidderToken)->postJson("/api/auctions/{$auction->id}/bid", [
            'amount' => 500,
        ]);
        $accept->assertOk()
            ->assertJsonPath('accepted', true)
            ->assertJsonPath('current_price', 500);

        $reject = $this->withToken($bidderToken)->postJson("/api/auctions/{$auction->id}/bid", [
            'amount' => 500,
        ]);
        $reject->assertStatus(422)
            ->assertJsonPath('accepted', false)
            ->assertJsonPath('error_code', 'LOW_BID');
    }

    public function test_it_handles_equal_amount_race_with_single_winner(): void
    {
        $seller = User::factory()->create();
        $bidderA = User::factory()->create();
        $bidderB = User::factory()->create();
        $auction = $this->createActiveAuction($seller, 400, CarbonImmutable::now()->addMinutes(5));

        $tokenA = $this->issueTokenFor($bidderA);
        $tokenB = $this->issueTokenFor($bidderB);

        $responseA = $this->withToken($tokenA)->postJson("/api/auctions/{$auction->id}/bid", [
            'amount' => 500,
        ]);
        $responseB = $this->withToken($tokenB)->postJson("/api/auctions/{$auction->id}/bid", [
            'amount' => 500,
        ]);

        $statuses = [$responseA->status(), $responseB->status()];
        sort($statuses);

        $this->assertSame([200, 422], $statuses);
        $this->assertDatabaseHas('auctions', [
            'id' => $auction->id,
            'current_price' => 500,
        ]);
    }

    public function test_it_extends_auction_when_bid_is_placed_in_final_thirty_seconds(): void
    {
        $seller = User::factory()->create();
        $bidder = User::factory()->create();
        $endTime = CarbonImmutable::now()->addSeconds(20);
        $auction = $this->createActiveAuction($seller, 100, $endTime);

        $response = $this->withToken($this->issueTokenFor($bidder))->postJson("/api/auctions/{$auction->id}/bid", [
            'amount' => 150,
        ]);

        $response->assertOk();
        $auction->refresh();

        $this->assertGreaterThanOrEqual(
            $endTime->addSeconds(59)->timestamp,
            CarbonImmutable::parse($auction->end_time)->timestamp
        );
    }

    public function test_it_creates_immutable_audit_logs_for_accepted_and_outbid_events(): void
    {
        $seller = User::factory()->create();
        $bidderA = User::factory()->create();
        $bidderB = User::factory()->create();
        $auction = $this->createActiveAuction($seller, 100, CarbonImmutable::now()->addMinutes(5));

        $this->withToken($this->issueTokenFor($bidderA))->postJson("/api/auctions/{$auction->id}/bid", [
            'amount' => 120,
            'request_id' => 'req-accepted',
        ])->assertOk();

        $this->withToken($this->issueTokenFor($bidderB))->postJson("/api/auctions/{$auction->id}/bid", [
            'amount' => 120,
            'request_id' => 'req-outbid',
        ])->assertStatus(422);

        $this->assertDatabaseHas('bid_logs', [
            'auction_id' => $auction->id,
            'event_type' => 'accepted',
            'request_id' => 'req-accepted',
        ]);
        $this->assertDatabaseHas('bid_logs', [
            'auction_id' => $auction->id,
            'event_type' => 'outbid',
            'request_id' => 'req-outbid',
        ]);
    }

    public function test_admin_can_withdraw_latest_bid_and_restore_previous_state(): void
    {
        $seller = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $bidderA = User::factory()->create();
        $bidderB = User::factory()->create();
        $auction = $this->createActiveAuction($seller, 100, CarbonImmutable::now()->addMinutes(5));

        $this->withToken($this->issueTokenFor($bidderA))->postJson("/api/auctions/{$auction->id}/bid", ['amount' => 120])->assertOk();
        $this->withToken($this->issueTokenFor($bidderB))->postJson("/api/auctions/{$auction->id}/bid", ['amount' => 150])->assertOk();

        $withdraw = $this->withToken($this->issueTokenFor($admin))->postJson("/api/auctions/{$auction->id}/withdraw-bid", [
            'reason' => 'Fraud check',
        ]);

        $withdraw->assertOk()->assertJsonPath('restored_price', 120);

        $auction->refresh();
        $this->assertSame('120.00', $auction->current_price);

        $this->assertDatabaseHas('bid_logs', [
            'auction_id' => $auction->id,
            'event_type' => 'withdrawn',
        ]);
    }

    private function createActiveAuction(User $seller, int|float $price, CarbonImmutable $endTime): Auction
    {
        return Auction::query()->create([
            'seller_id' => $seller->id,
            'starting_price' => $price,
            'current_price' => $price,
            'current_winner_id' => null,
            'end_time' => $endTime,
            'status' => 'active',
        ]);
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
