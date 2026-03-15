<?php

use App\Http\Controllers\GraphqlController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auction-room', ['auctionId' => 1]);
});

Route::get('/auction/{auctionId}', function (int $auctionId) {
    return view('auction-room', ['auctionId' => $auctionId]);
});

Route::post('/graphql', GraphqlController::class)
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
