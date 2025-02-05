<?php

namespace App\Http\Controllers;

use App\Facades\JSONLog;
use App\Http\Requests\OctoRequest;
use App\Services\BookingConfirmationService;
use App\Services\JSONLogService;
use App\Services\UnitService;
use App\Transformers\BaseTransformer;
use App\Transformers\BookingTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookingConfirmationController
{
    public BookingTransformer $transformer;
    public UnitService $unitService;

    public function __construct(public BookingConfirmationService $service, public JSONLogService $logger)
    {
        $this->transformer = new BookingTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->unitService = new UnitService();
    }

    public function index(Request $request, string $uuid): JsonResponse
    {
        $this->logger->info(["message" => "Starting to process booking confirmation request", "request" => $request->post()]);

        $contact = $request->post(OctoRequest::CONTACT);

        $this->logger->info(["message" => "Getting booking from database", "Booking_uuid" => $uuid]);
        $booking = $this->service->getBookingByUuid($uuid);

        $unitItems = $request->post(OctoRequest::UNIT_ITEMS);
        if (!empty($unitItems)) {
            $this->unitService->validateUnitItems($unitItems);
        }

        $resellerReference = $request->post(OctoRequest::RESELLER_REFERENCE);
        if (!empty($resellerReference)) {
            $booking = $booking->setResellerReference($resellerReference);
        }
        $this->logger->info(["message" => "Confirming booking", "Booking_uuid" => $uuid, "Booking_id" => $booking->getId()]);
        $booking = $this->service->confirmBooking($booking);
        // if contact update lead customer
        if (!empty($contact)) {
            $this->service->addContactToBooking($contact, $booking);
        }
        $bookingData = $this->transformer->transform($booking);
        $this->logger->info(["message" => "Request processed, returning response", "response" => $bookingData]);

        return new JsonResponse($bookingData, Response::HTTP_OK);
    }
}