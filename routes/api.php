<?php

use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingReservationController;
use App\Http\Controllers\BookingConfirmationController;
use App\Http\Controllers\SupplierController;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Responses\OctoResponse;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Http\Controllers\ProductController;

Route::get('/auth', function(): JsonResponse { return OctoResponse::OK('OK'); })->middleware([OctoAuthentication::class]);

Route::get('/supplier', [SupplierController::class, 'index'])->middleware([OctoAuthentication::class]);

Route::get('/suppliers', [SupplierController::class, 'index'])->middleware([OctoAuthentication::class]);

Route::get('products', [ProductController::class, 'index'])->middleware([OctoAuthentication::class]);

Route::get('products/{id}', [ProductController::class, 'show'])->middleware([OctoAuthentication::class]);

Route::post('/availability', [AvailabilityController::class, 'index'])->middleware([OctoAuthentication::class]);

Route::post('/bookings', [BookingReservationController::class, 'index'])->middleware([OctoAuthentication::class]);

Route::post('/bookings/{uuid}/confirmation', [BookingConfirmationController::class, 'index'])->middleware([OctoAuthentication::class]);