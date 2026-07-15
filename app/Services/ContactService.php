<?php

namespace App\Services;

use App\Models\Contact;
use SimpleXMLElement;

class ContactService
{
    private const MISSING_PHONE_NUMBER = 'MISSING_PHONE_NUMBER';
    private const MISSING_EMAIL_ADDRESS = 'MISSING_EMAIL_ADDRESS';
    private const MISSING_NOTES = 'MISSING_NOTES';
    private const MISSING_POSTAL_CODE = 'MISSING_POSTAL_CODE';
    private const MISSING_COUNTRY = 'MISSING_COUNTRY';

    public function getCustomerXMLFromContact(int $customerId, Contact $contact): SimpleXMLElement
    {
        $customerXML = new SimpleXMLElement('<customer />');
        $customerXML->addChild('customer_id', $customerId);

        if (!empty($contact->getFirstName()) && !empty($contact->getLastName())) {
            $customerXML->addChild('firstname', $contact->getFirstName());
            $customerXML->addChild('surname', $contact->getLastName());
        } else if (!empty($contact->getFullName())) {
            $customerXML->addChild('firstname', $contact->getFullName());
        }

        $customerXML->addChild('email', $contact->getEmailAddress());
        $customerXML->addChild('country', $contact->getCountry());
        $customerXML->addChild('tel_mobile', $contact->getPhoneNumber());
        $customerXML->addChild('postcode', $contact->getPostalCode());
        $customerXML->addChild('contact_note', $contact->getNotes());

        return $customerXML;
    }

    public function completeLeaderPaxContactData(Contact $newLeadContactData, Contact $originalLeadContactData): Contact
    {
        $missingDataArray = $this->missingContactDataForLead($newLeadContactData);

        if (empty($missingDataArray)) {
            return $newLeadContactData;
        }

        foreach ($missingDataArray as $missingData) {
            match ($missingData) {
                self::MISSING_PHONE_NUMBER  => $newLeadContactData->setPhoneNumber($originalLeadContactData->getPhoneNumber()),
                self::MISSING_EMAIL_ADDRESS => $newLeadContactData->setEmailAddress($originalLeadContactData->getEmailAddress()),
                self::MISSING_NOTES         => $newLeadContactData->setNotes($originalLeadContactData->getNotes()),
                self::MISSING_POSTAL_CODE   => $newLeadContactData->setPostalCode($originalLeadContactData->getPostalCode()),
                self::MISSING_COUNTRY       => $newLeadContactData->setCountry($originalLeadContactData->getCountry())
            };
        }

        return $newLeadContactData;
    }

    private function missingContactDataForLead(Contact $contact): array
    {
        $missingData = [];

        $contact->getEmailAddress() ??  $missingData[] = self::MISSING_EMAIL_ADDRESS;
        $contact->getCountry()      ??  $missingData[] = self::MISSING_COUNTRY;
        $contact->getPhoneNumber()  ??  $missingData[] = self::MISSING_PHONE_NUMBER;
        $contact->getPostalCode()   ??  $missingData[] = self::MISSING_POSTAL_CODE;
        $contact->getNotes()        ??  $missingData[] = self::MISSING_NOTES;

        return $missingData;
    }
}