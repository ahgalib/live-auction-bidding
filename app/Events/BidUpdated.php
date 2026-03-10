<?php

namespace App\Events;

use App\Models\Auction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BidUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Auction $auction,
        public string $updateType,
        public int $eventTimestampMs,
    ) {
    }
}
