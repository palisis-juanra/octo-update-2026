<?php

namespace App\Services;

use App\Models\Contact;

class BookingContactService
{
    public ContactService $contactService;

    public function __construct()
    {
        $this->contactService = new ContactService();
    }

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
    ): Contact|null
    {
        if ($unitUUID != $firstBookingUnitArrayUUID) {
            return $leadContact;
        }

        if (empty($leadContact)) {
            return $unitContact;
        }

        return $this->contactService->completeLeaderPaxContactData($unitContact, $leadContact);
    }
}
