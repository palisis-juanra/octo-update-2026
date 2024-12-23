<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingNotCancellableException;
use App\Models\Booking;
use App\Services\BookingCancellationService;
use App\Services\JSONLogService;
use App\Transformers\BaseTransformer;
use App\Transformers\BookingTransformer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BookingCancellationController extends Controller
{
    public const FIELD_REASON = 'reason';
    public const FIELD_FORCE = 'force';
    public BookingTransformer $transformer;
    public function __construct(
        public BookingCancellationService $bookingCancelService,
        public JSONLogService $logger
    ) 
    {
        $this->transformer = new BookingTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function cancel(Request $request, $uuid): JsonResponse
    {
        $requestParams = $request->post();
        $this->logger->info(["message" => "Starting to process cancel booking request", "request" => $request->post()]);

        $reason = $requestParams[self::FIELD_REASON] ?? null;
        $force = $requestParams[self::FIELD_FORCE] ?? null;

        $booking = $this->bookingCancelService->getBookingByUuid($uuid);

        $bookingObject = $this->bookingCancelService->getBookingObject($booking);
        
        $this->bookingCancelService->isBookingCancellable($bookingObject);

        $this->bookingCancelService->cancelBooking($bookingObject, $reason, $force);

        $booking = $this->bookingCancelService->getBookingObject($booking);
        
        $this->bookingCancelService->updateBookingStatusToCancelled($booking);

        $bookingData = $this->transformer->transform($booking);

        return new JsonResponse($bookingData, Response::HTTP_OK);
    }

}