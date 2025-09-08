<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingNotCancellableException;
use App\Models\Booking;
use App\Models\BookingCancellation;
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

        $bookingByUUID = $this->bookingCancelService->getBookingByUuid($uuid);

        $booking = $this->bookingCancelService->getBooking($bookingByUUID, false);
        $bookingJSONFromDB = $this->bookingCancelService->getBookingObjectFromJSON(json_decode($bookingByUUID->complete_booking_json, true));

        if (!$booking->isBookingCancellable()) { 
            $this->logger->info("Booking not cancellable: {$booking->getId()}");
            throw new BookingNotCancellableException;
        };

        if ($this->bookingCancelService->shouldWeCancelBooking($booking)) {
            $this->logger->info("Booking {$booking->getUuid()} is confirmed, calling cancel booking endpoint");
            $cancelled = $this->bookingCancelService->cancelBooking($booking, $reason);

        } else {
            $this->logger->info("Booking {$booking->getUuid()} is temporary, calling delete booking endpoint");
            $cancelled = $this->bookingCancelService->deleteBooking($booking);
        }

        if (true === $cancelled) {
            $this->bookingCancelService->updateBookingStatusToCancelled($booking);
            $cancellation = new BookingCancellation($reason);
            $booking->setCancellation($cancellation);
        }

        $bookingData = $this->transformer->transform($booking);
        $transformedBooking = $this->bookingCancelService->updateStoredJsonWithNewInformation($bookingJSONFromDB, (object)$bookingData);
        $bookingByUUID->update(['complete_booking_json' => json_encode($transformedBooking), 'status' => Booking::STATUS_CANCELLED]);

        return new JsonResponse($transformedBooking, Response::HTTP_OK);
    }
}