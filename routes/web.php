<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IssueController;
use App\Http\Controllers\Admin\BikeController;
use App\Http\Controllers\Admin\BikePriceController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\ReservationSettingController;
use App\Http\Controllers\Admin\StationController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::post('/logout', [AuthController::class, 'destroy'])
        ->middleware('admin')
        ->name('logout');

    Route::middleware('admin')->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('stations', StationController::class)->except(['show']);
        Route::resource('bikes', BikeController::class)->except(['show']);
        Route::resource('bike-prices', BikePriceController::class)->except(['show']);
        Route::get('reservations/availability', [ReservationController::class, 'availability'])->name('reservations.availability');
        Route::resource('reservations', ReservationController::class)->except(['show']);
        Route::resource('reservation-settings', ReservationSettingController::class)->except(['show']);
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('issues', IssueController::class)->except(['show']);
        Route::resource('notifications', NotificationController::class)->except(['show']);
    });
});
