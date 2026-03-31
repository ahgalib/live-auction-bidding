<?php

use App\Http\Controllers\GraphqlController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'velocity-auction-backend',
        'status' => 'ok',
    ]);
});

Route::post('/graphql', GraphqlController::class)
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
