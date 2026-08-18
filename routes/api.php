<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BikeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\StationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/auth/resend-email-code', [AuthController::class, 'resendEmailVerification']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/settings', SettingController::class);

    Route::middleware('api.token')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/me', [AuthController::class, 'update']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/dashboard', DashboardController::class);
        Route::get('/stations', [StationController::class, 'index']);
        Route::get('/bikes', [BikeController::class, 'index']);
        Route::get('/bikes/{bike}', [BikeController::class, 'show']);
        Route::get('/bikes/{bike}/availability', [BikeController::class, 'availability']);
        Route::get('/reservations', [ReservationController::class, 'index']);
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);
        Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::get('/issues', [IssueController::class, 'index']);
        Route::post('/issues', [IssueController::class, 'store']);
    });
});
