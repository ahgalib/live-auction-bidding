<?php

namespace App\Events;

use App\Models\Auction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BidUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Auction $auction,
        public ?string $bidderName,
        public ?float $amount,
        public ?string $endTime,
        public string $updateType,
        public int $eventTimestampMs,
        public ?int $participantCount = null,
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel("auction.{$this->auction->id}");
    }

    public function broadcastAs(): string
    {
        return 'BidUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'auctionId' => (string) $this->auction->id,
            'amount' => $this->amount,
            'bidderName' => $this->bidderName,
            'endTime' => $this->endTime,
            'updateType' => $this->updateType,
            'eventTimestampMs' => $this->eventTimestampMs,
            'participantCount' => $this->participantCount,
        ];
    }
}
