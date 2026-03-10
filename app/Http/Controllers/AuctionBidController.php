<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Services\AuctionBiddingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AuctionBidController extends Controller
{
    public function placeBid(Request $request, Auction $auction, AuctionBiddingService $service): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'request_id' => ['nullable', 'string', 'max:64'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $result = $service->placeBid(
            $auction,
            $user,
            (float) $validated['amount'],
            $request->ip(),
            $validated['request_id'] ?? null,
        );

        return response()->json($result, $result['accepted'] ? 200 : 422);
    }

    public function withdrawLatestBid(Request $request, Auction $auction, AuctionBiddingService $service): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $result = $service->withdrawLatestBid($auction, $user, $validated['reason'] ?? null);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'FORBIDDEN') {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            if ($e->getMessage() === 'NO_BID_TO_WITHDRAW') {
                return response()->json(['message' => 'No bid to withdraw'], 422);
            }
            throw $e;
        }

        return response()->json($result);
    }
}
