<?php

use App\Http\Controllers\AvailabilityCalendarController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingCancellationController;
use App\Http\Controllers\BookingConfirmationController;
use App\Http\Controllers\BookingGetController;
use App\Http\Controllers\BookingReservationController;
use App\Http\Controllers\BookingUpdateController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Middleware\AgentWithFullBookingPermissions;
use App\Http\Middleware\APIJsonLogger;
use App\Http\Middleware\CapabilitiesHeader;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Requests\OctoRequest;
use App\Http\Responses\OctoResponse;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// All routes have authentication and capabilities

Route::middleware([OctoAuthentication::class, CapabilitiesHeader::class])->group(function () {

    Route::get('/auth', function (): JsonResponse {
        return OctoResponse::OK('OK');
    })->middleware([OctoAuthentication::class]);

    // Supplier

    Route::get('/supplier', [SupplierController::class, 'index'])
        ->name(OctoRequest::ENDPOINT_SUPPLIER_GET);

    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->name(OctoRequest::ENDPOINT_SUPPLIERS_GET);

    // Products

    Route::get('products', [ProductController::class, 'index'])
        ->name(OctoRequest::ENDPOINT_PRODUCTS_GET);

    Route::get('products/{id}', [ProductController::class, 'show'])
        ->name(OctoRequest::ENDPOINT_PRODUCT_GET);

    // Availability

    Route::post('/availability', [AvailabilityController::class, 'index'])
        ->name(OctoRequest::ENDPOINT_AVAILABILITY_CHECK)
        ->middleware([APIJsonLogger::class]);

    Route::post('/availability/calendar', [AvailabilityCalendarController::class, 'index'])
        ->name(OctoRequest::ENDPOINT_AVAILABILITY_CALENDAR);

    // Bookings

    Route::middleware(AgentWithFullBookingPermissions::class)->group(function () {

        Route::get('/bookings/{uuid}', [BookingGetController::class, 'show'])
            ->name(OctoRequest::ENDPOINT_BOOKING_GET);

        Route::post('/bookings', [BookingReservationController::class, 'index'])
            ->name(OctoRequest::ENDPOINT_BOOKINGS_RESERVATION)
            ->middleware([APIJsonLogger::class]);

        Route::post('/bookings/{uuid}/confirm', [BookingConfirmationController::class, 'index'])
            ->name(OctoRequest::ENDPOINT_BOOKINGS_CONFIRMATION)
            ->middleware([APIJsonLogger::class]);

        Route::post('/bookings/{uuid}/cancel', [BookingCancellationController::class, 'cancel'])
            ->name(OctoRequest::ENDPOINT_BOOKINGS_CANCELLATION)
            ->middleware([APIJsonLogger::class]);

        Route::patch('/bookings/{uuid}', [BookingUpdateController::class, 'update'])
            ->name(OctoRequest::ENDPOINT_BOOKINGS_UPDATE)
            ->middleware([APIJsonLogger::class]);
    });

    // Endpoints not implemented

    Route::post('/bookings/{uuid}/extend', function () {
        throw new NotFoundHttpException;
    })
        ->name(OctoRequest::ENDPOINT_BOOKINGS_EXTEND);

    Route::get('/bookings', function () {
        throw new NotFoundHttpException;
    })
        ->name(OctoRequest::ENDPOINT_BOOKINGS_GET);

});
