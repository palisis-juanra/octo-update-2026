<?php

namespace App\Http\Controllers;

use App\Http\Requests\OctoRequest;
use App\Models\Contact;
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

        
        $this->logger->info(["message" => "Getting booking from database", "Booking_uuid" => $uuid]);
        $booking = $this->service->getBookingByUuid($uuid);
        
        $unitItems = $request->post(OctoRequest::UNIT_ITEMS) ?? [];
        if (!empty($unitItems)) {
            $this->unitService->validateUnitItems($unitItems);
            // check unit items same as reservation
            $this->service->checkUnitItemsHaveNotChanged($booking, $unitItems);
        }
        
        $resellerReference = $request->post(OctoRequest::RESELLER_REFERENCE);
        if (!empty($resellerReference)) {
            $booking = $booking->setResellerReference($resellerReference);
        }
        $this->logger->info(["message" => "Confirming booking", "uuid" => $uuid, "id" => $booking->getId()]);
        $booking = $this->service->confirmBooking($booking);
        

        $leadTravellerContactData = $request->post(OctoRequest::CONTACT);
        if (!empty($leadTravellerContactData)) {
            $contact = Contact::create($leadTravellerContactData);
            $this->service->updateTraveller($booking->getLeadCustomerId(), $contact);
            $booking->setContact($contact);
        }
        
        $booking->getUnits();

        foreach ($unitItems as $unitItem) {
            if (!array_key_exists('contact', $unitItem)) {
                continue;
            }
            // We dont know the customer id
            //$this->service->updateTraveller((int) $unitItem['unitId'], Contact::create($unitItem['contact']));
        }

        $bookingData = $this->transformer->transform($booking);
        $this->logger->info(["message" => "Request processed, returning response", "response" => $bookingData]);

        return new JsonResponse($bookingData, Response::HTTP_OK);
    }
}