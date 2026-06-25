<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\WebhookController;

// Public Auth Endpoints (Rate Limited to prevent brute force)
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Public Webhooks (Rate Limited to prevent spam)
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/webhooks/setmore', [WebhookController::class, 'setmore']);
    Route::post('/webhooks/stripe', [WebhookController::class, 'stripe']);
});

// Protected Client Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/users', [AuthController::class, 'index']);

    // Bookings / Operational Tickets
    Route::apiResource('bookings', BookingController::class);

    // Document Management
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents/{id}/download', [DocumentController::class, 'download']);

    // Live Chat Messages
    Route::get('/bookings/{id}/messages', [\App\Http\Controllers\Api\MessageController::class, 'index']);
    Route::post('/bookings/{id}/messages', [\App\Http\Controllers\Api\MessageController::class, 'store']);
});
