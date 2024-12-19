<?php

namespace App\Http\Controllers;

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
        $this->logger->info(["message" => "Starting to process request", "request" => $request->post()]);

        $reason = $requestParams[self::FIELD_REASON] ?? null;
        $force = $requestParams[self::FIELD_FORCE] ?? null;

        $booking = $this->bookingCancelService->getBookingByUuid($uuid);
        $bookingResponse = $this->bookingCancelService->getBookingResponse($booking);
        $bookingCancelData = $this->bookingCancelService->cancelBooking($bookingResponse, $reason, $force);

        $bookingData = $this->transformer->transform($bookingCancelData);

        return new JsonResponse($bookingData, Response::HTTP_OK);
    }

}