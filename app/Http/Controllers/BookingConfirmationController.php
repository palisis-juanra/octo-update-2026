<?php

namespace App\Http\Controllers;

use App\Http\Requests\OctoRequest;
use App\Models\Booking;
use App\Models\Contact;
use App\Services\BookingConfirmationService;
use App\Services\BookingContactService;
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

    public function __construct(public BookingConfirmationService $service, public JSONLogService $logger, public BookingContactService $bookingContactService)
    {
        $this->transformer = new BookingTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->unitService = new UnitService();
    }

    public function index(Request $request, string $uuid): JsonResponse
    {
        $this->logger->info(["message" => "Starting to process booking confirmation request", "request" => $request->post()]);
        $this->logger->info(["message" => "Getting booking from database", "Booking_uuid" => $uuid]);

        $bookingByUUID = $this->service->getBookingByUuid($uuid);
        $booking = $this->service->getBooking($bookingByUUID);

        if ($booking->isAlreadyConfirmed()) {
            $bookingData = $this->transformer->transform($booking);
            $this->logger->info(["message" => "Booking already confirmed", "uuid" => $uuid, "id" => $booking->getId()]);
            return new JsonResponse($bookingData, Response::HTTP_OK);
        }

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

        // We need to remove unit items without contact, as they are not needed
        $unitContacts = $this->bookingContactService->createUnitContactsArray($unitItems);

        $leadTravellerContactData = $request->post(OctoRequest::CONTACT);
        $originalLeadContact = $this->bookingContactService->createLeadTravellerContact($leadTravellerContactData);
        $leadContact = $originalLeadContact;

        $leadContact = $this->bookingContactService->updateBookingTravelersWithContactInfo($unitContacts, $booking, $leadContact);

        // Update customers information
        $booking = $this->bookingContactService->updateBookingLeadCustomerContactInfo($booking, $leadContact);

        // Commit booking
        $this->logger->info(["message" => "Confirming booking", "uuid" => $uuid, "id" => $booking->getId()]);
        $booking = $this->service->confirmBooking($booking);

        if (!empty($originalLeadContact)) {
            $booking->setContact($originalLeadContact);
        }

        $unitItems = $booking->getUnits();

        $bookingData = $this->transformer->transform($booking);
        $bookingByUUID->update(['status' => Booking::STATUS_CONFIRMED, 'complete_booking_json' => json_encode($bookingData), 'unit_items' => json_encode($unitItems)]);

        $this->service->createOriginalCustomerDetailsAuditNote($booking, $leadTravellerContactData);

        $this->logger->info(["message" => "Request processed, returning response", "response" => $bookingData]);

        return new JsonResponse($bookingData, Response::HTTP_OK);
    }
}