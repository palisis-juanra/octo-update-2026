<?php

namespace App\Http\Controllers;

use App\Http\Requests\OctoRequest;
use App\Models\Booking;
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
        $unitContacts = [];
        foreach ($unitItems as $key => $unitItem) {
            if (array_key_exists('contact', $unitItem) && !empty($unitItem['contact']) && is_array($unitItem['contact'])) {
                $unitContacts[] = $unitItem;
            }
        }

        $leadTravellerContactData = $request->post(OctoRequest::CONTACT);
        $leadContact = null;

        if (!empty($leadTravellerContactData)) {
            $originalLeadContact = Contact::create($leadTravellerContactData);
            $leadContact = $originalLeadContact;
        }

        if (!empty($unitContacts)) {
            // Update unit items
            $bookingUnitsArray = $booking->getUnits();
            foreach ($bookingUnitsArray as $unitItem) {
                $unitIds = array_column($unitContacts, 'unitId');
                $key = array_search($unitItem->getId(), $unitIds);
                $contact = Contact::create($unitContacts[$key]['contact'] ?? []);
                if ($unitItem->uuid === $bookingUnitsArray[array_key_first($bookingUnitsArray)]->uuid) {

                    if (!empty($leadContact)) {
                        $leadContact = $this->service->contactService->completeLeaderPaxContactData($contact, $leadContact);
                        continue;
                    }

                    $leadContact = $contact;
                }
                $this->service->updateTraveller($unitItem->getCustomerId(), $contact);
                unset($unitContacts[$key]);
                sort($unitContacts);
            }
        }

        // Update customers information
        if (!empty($leadContact)) {
            $this->service->updateTraveller($booking->getLeadCustomerId(), $leadContact);
            $booking->setContact($leadContact);
        }

        // Commit booking
        $this->logger->info(["message" => "Confirming booking", "uuid" => $uuid, "id" => $booking->getId()]);
        $booking = $this->service->confirmBooking($booking);

        if (!empty($originalLeadContact)) {
            $booking->setContact($originalLeadContact);
        }

        $unitItems = $booking->getUnits();

        $bookingData = $this->transformer->transform($booking);
        $bookingByUUID->update(['status' => Booking::STATUS_CONFIRMED, 'complete_booking_json' => json_encode($bookingData), 'unit_items' => json_encode($unitItems)]);

        $note = "Booking lead passenger details\n\n Lead passenger details:\n" . json_encode($leadTravellerContactData);
        $this->service->tourCMSService->callTourCMSAddNoteToBooking($booking->getChannelId(), $booking->getBookingId(), $note);

        $this->logger->info(["message" => "Request processed, returning response", "response" => $bookingData]);

        return new JsonResponse($bookingData, Response::HTTP_OK);
    }
}