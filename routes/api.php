<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Booking API Routes
|--------------------------------------------------------------------------
*/

// Services catalog
Route::get('/services', [ServiceController::class, 'index']);

// Availability
Route::get('/availability', [AvailabilityController::class, 'index']);
Route::get('/availability/dates', [AvailabilityController::class, 'dates']);

// Bookings
Route::prefix('bookings')->group(function () {
    Route::post('/hold', [BookingController::class, 'hold']);
    Route::get('/{id}', [BookingController::class, 'show']);
    Route::post('/{id}/cancel', [BookingController::class, 'cancel']);
});
