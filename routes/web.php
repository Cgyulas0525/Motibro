<?php

use App\Http\Controllers\BookingAttemptController;
use App\Http\Controllers\BookingRuleController;
use App\Http\Controllers\BookingRunController;
use App\Http\Controllers\BookingRunTriggerController;
use App\Http\Controllers\BookingSlotBookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchedulerSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('booking-rules', BookingRuleController::class)->except(['show']);
    Route::get('settings/scheduler', [SchedulerSettingsController::class, 'edit'])->name('settings.scheduler.edit');
    Route::patch('settings/scheduler', [SchedulerSettingsController::class, 'update'])->name('settings.scheduler.update');
    Route::post('booking-runs/trigger', BookingRunTriggerController::class)->name('booking-runs.trigger');
    Route::resource('booking-runs', BookingRunController::class)->only(['index', 'show', 'destroy']);
    Route::resource('booking-slot-bookings', BookingSlotBookingController::class)->only(['index', 'destroy']);
    Route::resource('booking-attempts', BookingAttemptController::class)->only(['index', 'destroy']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
