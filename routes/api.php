<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile / token API (Laravel Sanctum personal access tokens)
|--------------------------------------------------------------------------
| Used by the EventPass Flutter app. Reuses the same services as the website, so
| booking rules, ticket validity and the atomic one-scan check-in are identical.
*/

Route::post('/login', [Api\AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/login/demo/{role}', [Api\AuthController::class, 'demo'])->middleware('throttle:20,1');

Route::get('/events', [Api\EventController::class, 'index']);
Route::get('/events/{event}', [Api\EventController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [Api\AuthController::class, 'me']);
    Route::post('/logout', [Api\AuthController::class, 'logout']);

    Route::get('/tickets', [Api\TicketController::class, 'index']);

    Route::middleware('role:organizer')->prefix('organizer')->group(function () {
        Route::get('/events', [Api\CheckInController::class, 'events']);
        Route::post('/events/{event}/checkin', [Api\CheckInController::class, 'verify'])->middleware('throttle:checkin');
    });
});
