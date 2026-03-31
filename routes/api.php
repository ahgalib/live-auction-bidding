<?php

use App\Http\Controllers\AuctionBidController;
use App\Http\Controllers\AuctionStateController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/oauth/token', [AuthController::class, 'oauthToken']);
Route::get('/auctions/{auction}', [AuctionStateController::class, 'show']);

Route::middleware('oauth')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/auctions/{auction}/bid', [AuctionBidController::class, 'placeBid']);
    Route::post('/auctions/{auction}/withdraw-bid', [AuctionBidController::class, 'withdrawLatestBid']);
});
