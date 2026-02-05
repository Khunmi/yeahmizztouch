<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Api\ServiceApiController;
use App\Http\Controllers\Api\AvailabilityApiController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\BlackoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ─────────────────────────────────────────────────────────────────────────────
// PUBLIC BOOKING ROUTES
// ─────────────────────────────────────────────────────────────────────────────

Route::get('/', function () {
    return redirect()->route('booking.index');
});

Route::prefix('book')->name('booking.')->group(function () {
    // Step 1: Select service
    Route::get('/', [BookingController::class, 'index'])->name('index');
    
    // Step 2: Select date/time
    Route::get('/service/{service}', [BookingController::class, 'selectDateTime'])->name('select-datetime');

    Route::post('/hold', [BookingController::class, 'createHold'])->name('hold');

    
    // Step 3: Enter details
    Route::get('/details/{holdUuid}', [BookingController::class, 'showDetailsForm'])->name('details');
    Route::post('/details/{holdUuid}', [BookingController::class, 'processDetails'])->name('details.process');

    
    // Payment callbacks
    Route::get('/success', [BookingController::class, 'success'])->name('success');
    Route::get('/cancel', [BookingController::class, 'cancel'])->name('cancel');
    
    // Confirmation page
    Route::get('/confirmation/{uuid}', [BookingController::class, 'confirmation'])->name('confirmation');
});

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN ROUTES (Authenticated)
// ─────────────────────────────────────────────────────────────────────────────

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Appointments
    Route::get('/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
    Route::resource('appointments', AppointmentController::class);
    
    // Services
    Route::post('/services/{service}/restore', [ServiceController::class, 'restore'])->name('services.restore')->withTrashed();
    Route::resource('services', ServiceController::class)->except(['show']);
    
    // Blackout Dates
    Route::resource('blackouts', BlackoutController::class)->only(['index', 'create', 'store', 'destroy']);
});

// ─────────────────────────────────────────────────────────────────────────────
// WEBHOOKS (No CSRF)
// ─────────────────────────────────────────────────────────────────────────────

Route::post('/webhooks/stripe', [PaymentController::class, 'handleWebhook'])
    ->withoutMiddleware(['web'])
    ->name('webhooks.stripe');
