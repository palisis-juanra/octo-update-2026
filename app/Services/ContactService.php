<?php

namespace App\Services;

use App\Models\Contact;
use SimpleXMLElement;

class ContactService
{
    public function getCustomerXMLFromContact(int $customerId, Contact $contact): SimpleXMLElement
    {
        $customerXML = new SimpleXMLElement('<customer />');
        $customerXML->addChild('customer_id', $customerId);
        $customerXML->addChild('firstname', $contact->getFirstName());
        $customerXML->addChild('surname', $contact->getLastName());
        $customerXML->addChild('email', $contact->getEmailAddress());
        $customerXML->addChild('country', $contact->getCountry());
        $customerXML->addChild('tel_home', $contact->getPhoneNumber());
        $customerXML->addChild('postcode', $contact->getPostalCode());
        $customerXML->addChild('contact_note', $contact->getNotes());

        return $customerXML;
    }
}