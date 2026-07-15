<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Contact;

class BookingContactService
{
    public function __construct(public ContactService $contactService, public BookingConfirmationService $bookingConfirmationService) {}

    /**
     * Populates missing fields in the lead customer contact using data from the
     * original provided contact.
     *
     * No matching rules are applied during this process. Only missing values are
     * copied, and the lead customer name remains unchanged.
     *
     * @param string $unitUUID The unit identifier.
     * @param string $firstBookingUnitArrayUUID The first booking unit identifier.
     * @param Contact $unitContact The unit contact.
     * @param Contact|null $leadContact The lead customer contact.
     *
     * @return Contact|null The updated lead contact, or null if no lead contact is provided.
     */
    public function assignLeadContactDataToFirstUnit(
        string $unitUUID,
        string $firstBookingUnitArrayUUID,
        Contact $unitContact,
        ?Contact $leadContact
    ): Contact|null {
        if ($unitUUID != $firstBookingUnitArrayUUID) {
            return $leadContact;
        }

        if (empty($leadContact)) {
            return $unitContact;
        }

        return $this->contactService->completeLeaderPaxContactData($unitContact, $leadContact);
    }

    public function createLeadTravellerContact(?array $leadTravellerContactData): Contact|null
    {
        if (empty($leadTravellerContactData)) {
            return null;
        }

        return Contact::create($leadTravellerContactData);
    }

    public function createUnitContactsArray(array $unitItems): array
    {
        $unitContacts = [];
        foreach ($unitItems as $key => $unitItem) {
            if (
                array_key_exists('contact', $unitItem) &&
                !empty($unitItem['contact']) && is_array(
                    $unitItem['contact']
                )
            ) {
                $unitContacts[] = $unitItem;
            }
        }

        return $unitContacts;
    }

    public function updateBookingTravelersWithContactInfo(
        array $unitContacts,
        Booking $booking,
        ?Contact $leadContact
    ): Contact|null {
        if (empty($unitContacts)) {
            return $leadContact;
        }

        // Update unit items
        $bookingUnitsArray = $booking->getUnits();
        foreach ($bookingUnitsArray as $unitItem) {
            $unitIds = array_column($unitContacts, 'unitId');
            $key = array_search($unitItem->getId(), $unitIds);
            $contact = Contact::create($unitContacts[$key]['contact'] ?? []);
            $leadContact = $this->assignLeadContactDataToFirstUnit(
                $unitItem->uuid,
                $bookingUnitsArray[array_key_first($bookingUnitsArray)]->uuid,
                $contact,
                $leadContact
            );

            $this->bookingConfirmationService->updateTraveller($unitItem->getCustomerId(), $contact);
            unset($unitContacts[$key]);
            $unitContacts = array_values($unitContacts);
        }

        return $leadContact;
    }

    public function updateBookingLeadCustomerContactInfo(Booking $booking, ?Contact $leadContact): Booking
    {
        if (empty($leadContact)) {
            return $booking;
        }

        $this->bookingConfirmationService->updateTraveller($booking->getLeadCustomerId(), $leadContact);
        $booking->setContact($leadContact);

        return $booking;
    }
}
