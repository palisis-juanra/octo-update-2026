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
        $booking = $this->service->getBooking($booking);
        
        $unitItems = $request->post(OctoRequest::UNIT_ITEMS) ?? [];
        if (!empty($unitItems)) {
            $this->unitService->validateUnitItems($unitItems);
            // check unit items same as reservation
            $this->service->checkUnitItemsHaveNotChanged($booking, $unitItems);
        }
        
        // Update reseller reference
        $resellerReference = $request->post(OctoRequest::RESELLER_REFERENCE);
        if (!empty($resellerReference)) {
            $booking = $booking->setResellerReference($resellerReference);
        }
        
        // Update customers information
        $leadTravellerContactData = $request->post(OctoRequest::CONTACT);
        if (!empty($leadTravellerContactData)) {
            $contact = Contact::create($leadTravellerContactData);
            $this->service->updateTraveller($booking->getLeadCustomerId(), $contact);
            $booking->setContact($contact);
        }
        
        $unitContacts = [];
        // We need to remove unit items without contact, as they are not needed
        foreach ($unitItems as $key => $unitItem) {
            if (array_key_exists('contact', $unitItem)) {
                $unitContacts[] = $unitItem;
            }
        }

        if (!empty($unitContacts)) {
            // Update unit items
            foreach ($booking->getUnits() as $unitItem) {
                $unitIds = array_column($unitContacts, 'unitId');
                $key = array_search($unitItem->getId(), $unitIds);
                $contact = Contact::create($unitContacts[$key]['contact']);
                $this->service->updateTraveller($unitItem->getCustomerId(), $contact);
                unset($unitContacts[$key]);
                sort($unitContacts);
            }
        }

        // Commit booking
        $this->logger->info(["message" => "Confirming booking", "uuid" => $uuid, "id" => $booking->getId()]);
        $booking = $this->service->confirmBooking($booking);

        $bookingData = $this->transformer->transform($booking);
        $this->logger->info(["message" => "Request processed, returning response", "response" => $bookingData]);

        return new JsonResponse($bookingData, Response::HTTP_OK);
    }
}