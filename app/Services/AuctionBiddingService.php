<?php

namespace App\Services;

use App\Events\BidUpdated;
use App\Models\Auction;
use App\Models\BidLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AuctionBiddingService
{
    private const CLOSED_TTL_SECONDS = 86400;

    /**
     * @return array<string, mixed>
     */
    public function placeBid(Auction $auction, User $user, float $amount, ?string $ipAddress, ?string $requestId = null): array
    {
        $startedAt = microtime(true);
        $lock = Cache::lock($this->lockKey($auction->id), 1);

        return $lock->block(1, function () use ($auction, $user, $amount, $ipAddress, $requestId, $startedAt): array {
            $idempotencyKey = $requestId ? $this->idempotencyKey($auction->id, $user->id, $requestId) : null;
            if ($idempotencyKey) {
                $cachedResult = Cache::get($idempotencyKey);
                if (is_array($cachedResult)) {
                    return $cachedResult;
                }
            }

            $auction->refresh();
            [$currentPrice, $endTime] = $this->hydrateLiveState($auction);

            if ($auction->status !== 'active' || $endTime->isPast()) {
                $this->markClosedTtl($auction->id);

                $result = $this->rejectBid('AUCTION_CLOSED', $currentPrice, $auction, $user, $amount, $ipAddress, $requestId);
                if ($idempotencyKey) {
                    Cache::put($idempotencyKey, $result, now()->addMinutes(2));
                }

                return $result;
            }

            $minimumAllowed = $currentPrice + (float) $auction->min_increment;
            if ($amount < $minimumAllowed) {
                $result = $this->rejectBid('LOW_BID', $currentPrice, $auction, $user, $amount, $ipAddress, $requestId);
                if ($idempotencyKey) {
                    Cache::put($idempotencyKey, $result, now()->addMinutes(2));
                }

                return $result;
            }

            // Auction time is controlled by admin actions only and must remain DB-canonical.
            $newEndTime = CarbonImmutable::parse($auction->end_time)->setTimezone('UTC');

            $this->writeLiveState($auction->id, $amount, $user->id, $newEndTime);

            $auction->forceFill([
                'current_price' => $amount,
                'current_winner_id' => $user->id,
            ])->save();

            $this->dispatchAudit($auction, $user, $amount, 'accepted', $ipAddress, $requestId, [
                'previous_price' => $currentPrice,
            ]);

            event(new BidUpdated(
                $auction->fresh(),
                $user->name,
                $amount,
                $newEndTime->toIso8601String(),
                'bid_accepted',
                now()->getTimestampMs(),
                $this->participantCount($auction->id),
            ));

            Log::info('auction.bid.accepted', [
                'auction_id' => $auction->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'latency_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'broadcast_target_ms' => 200,
            ]);

            $result = [
                'accepted' => true,
                'error_code' => null,
                'current_price' => $amount,
                'winner_id' => $user->id,
                'end_time' => $newEndTime->toIso8601String(),
                'event_timestamp' => now()->toIso8601String(),
            ];

            if ($idempotencyKey) {
                Cache::put($idempotencyKey, $result, now()->addMinutes(2));
            }

            return $result;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function withdrawLatestBid(Auction $auction, User $admin, ?string $reason): array
    {
        if (! $admin->is_admin) {
            throw new RuntimeException('FORBIDDEN');
        }

        $lock = Cache::lock($this->lockKey($auction->id), 1);

        return $lock->block(1, function () use ($auction, $admin, $reason): array {
            $latestAccepted = BidLog::query()
                ->where('auction_id', $auction->id)
                ->where('event_type', 'accepted')
                ->latest('id')
                ->first();

            if (! $latestAccepted) {
                throw new RuntimeException('NO_BID_TO_WITHDRAW');
            }

            $previousAccepted = BidLog::query()
                ->where('auction_id', $auction->id)
                ->where('event_type', 'accepted')
                ->where('id', '<', $latestAccepted->id)
                ->latest('id')
                ->first();

            $restoredPrice = $previousAccepted ? (float) $previousAccepted->amount : (float) $auction->starting_price;
            $restoredWinnerId = $previousAccepted?->user_id;

            $auction->forceFill([
                'current_price' => $restoredPrice,
                'current_winner_id' => $restoredWinnerId,
            ])->save();

            $this->writeLiveState(
                $auction->id,
                $restoredPrice,
                $restoredWinnerId,
                CarbonImmutable::parse($auction->end_time)->setTimezone('UTC')
            );

            $this->dispatchAudit($auction, $admin, $restoredPrice, 'withdrawn', null, null, [
                'withdrawn_bid_log_id' => $latestAccepted->id,
                'reason' => $reason,
            ]);

            event(new BidUpdated(
                $auction->fresh(),
                $admin->name,
                $restoredPrice,
                CarbonImmutable::parse($auction->end_time)->setTimezone('UTC')->toIso8601String(),
                'bid_withdrawn',
                now()->getTimestampMs(),
                $this->participantCount($auction->id),
            ));

            return [
                'success' => true,
                'restored_price' => $restoredPrice,
                'restored_winner_id' => $restoredWinnerId,
                'event_timestamp' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * @return array{0: float, 1: CarbonImmutable}
     */
    private function hydrateLiveState(Auction $auction): array
    {
        $priceKey = $this->priceKey($auction->id);
        $winnerKey = $this->winnerKey($auction->id);
        $cachedPrice = Cache::get($priceKey);
        if ($cachedPrice === null) {
            $this->writeLiveState(
                $auction->id,
                (float) $auction->current_price,
                $auction->current_winner_id,
                CarbonImmutable::parse($auction->end_time)->setTimezone('UTC'),
            );
            $cachedPrice = (string) $auction->current_price;
            if ($auction->current_winner_id !== null) {
                Cache::put($winnerKey, (string) $auction->current_winner_id);
            }
        }

        // Keep end time DB-canonical to avoid stale cache reversion on bid submissions.
        return [(float) $cachedPrice, CarbonImmutable::parse($auction->end_time)->setTimezone('UTC')];
    }

    private function writeLiveState(int $auctionId, float $price, ?int $winnerId, CarbonImmutable $endTime): void
    {
        Cache::put($this->priceKey($auctionId), (string) $price);
        Cache::put($this->endTimeKey($auctionId), $endTime->toIso8601String());

        if ($winnerId === null) {
            Cache::forget($this->winnerKey($auctionId));
        } else {
            Cache::put($this->winnerKey($auctionId), (string) $winnerId);
        }
    }

    private function markClosedTtl(int $auctionId): void
    {
        $now = now()->addSeconds(self::CLOSED_TTL_SECONDS);
        foreach ([$this->priceKey($auctionId), $this->winnerKey($auctionId), $this->endTimeKey($auctionId)] as $key) {
            $value = Cache::get($key);
            if ($value !== null) {
                Cache::put($key, $value, $now);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rejectBid(string $errorCode, float $currentPrice, Auction $auction, User $user, float $amount, ?string $ipAddress, ?string $requestId): array
    {
        $eventType = $errorCode === 'LOW_BID' ? 'outbid' : 'rejected';
        $this->dispatchAudit($auction, $user, $amount, $eventType, $ipAddress, $requestId, [
            'error_code' => $errorCode,
            'current_price' => $currentPrice,
        ]);

            return [
                'accepted' => false,
                'error_code' => $errorCode,
                'current_price' => $currentPrice,
                'min_increment' => (float) $auction->min_increment,
                'winner_id' => $auction->current_winner_id,
                'end_time' => CarbonImmutable::parse($auction->end_time)->setTimezone('UTC')->toIso8601String(),
                'event_timestamp' => now()->toIso8601String(),
            ];
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function dispatchAudit(Auction $auction, ?User $user, float $amount, string $eventType, ?string $ipAddress, ?string $requestId, array $meta = []): void
    {
        $payload = [
            'auction_id' => $auction->id,
            'user_id' => $user?->id,
            'amount' => $amount,
            'event_type' => $eventType,
            'request_id' => $requestId,
            'ip_address' => $ipAddress,
            'meta' => $meta,
            'created_at' => now(),
        ];

        BidLog::query()->create($payload);
    }

    private function lockKey(int $auctionId): string
    {
        return "auction:{$auctionId}:lock";
    }

    private function priceKey(int $auctionId): string
    {
        return "auction:{$auctionId}:current_price";
    }

    private function winnerKey(int $auctionId): string
    {
        return "auction:{$auctionId}:current_winner_id";
    }

    private function endTimeKey(int $auctionId): string
    {
        return "auction:{$auctionId}:end_time";
    }

    private function idempotencyKey(int $auctionId, int $userId, string $requestId): string
    {
        return "auction:{$auctionId}:user:{$userId}:request:{$requestId}";
    }

    private function participantCount(int $auctionId): int
    {
        return DB::table('bid_logs')
            ->where('auction_id', $auctionId)
            ->where('event_type', 'accepted')
            ->distinct('user_id')
            ->count('user_id');
    }
}
