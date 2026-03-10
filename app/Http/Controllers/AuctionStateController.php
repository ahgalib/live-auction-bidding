<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use Illuminate\Http\JsonResponse;

class AuctionStateController extends Controller
{
    public function show(Auction $auction): JsonResponse
    {
        return response()->json([
            'auction_id' => $auction->id,
            'current_price' => (float) $auction->current_price,
            'current_winner_id' => $auction->current_winner_id,
            'end_time' => $auction->end_time?->toIso8601String(),
            'status' => $auction->status,
        ]);
    }

    public function serverTime(): JsonResponse
    {
        return response()->json([
            'server_time_utc' => now()->toIso8601String(),
            'timestamp_precision' => 'milliseconds',
        ]);
    }
}
