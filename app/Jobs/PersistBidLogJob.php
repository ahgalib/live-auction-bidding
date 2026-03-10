<?php

namespace App\Jobs;

use App\Models\BidLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PersistBidLogJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(private array $payload)
    {
    }

    public function handle(): void
    {
        BidLog::query()->create($this->payload);
    }
}
