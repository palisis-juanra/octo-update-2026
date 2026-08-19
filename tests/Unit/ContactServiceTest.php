<?php

namespace App\Tests;

use App\Models\Contact;
use App\Services\ContactService;
use Tests\UnitTestCase;

class ContactServiceTest extends UnitTestCase
{
    public const int FAKE_CUSTOMER_ID = 1;

    public const string FAKE_FULLNAME = 'John Doe';

    public const string FAKE_FIRSTNAME = 'Joe';

    public const string FAKE_LASTNAME = 'Bloggs';

    public function test_when_creating_customer_xml_from_contact_with_full_name_then_we_get_customer_xml_with_fistname_filled(): void
    {
        $contactService = new ContactService;
        $contact = (new Contact)->setFullName(self::FAKE_FULLNAME);

        $customerXML = $contactService->getCustomerXMLFromContact(self::FAKE_CUSTOMER_ID, $contact);

        $this->assertEquals(self::FAKE_FULLNAME, (string) $customerXML->firstname);
    }

    public function test_when_creating_customer_xml_from_contact_with_full_name_and_firstname_and_surname_then_we_get_customer_name_from_firstname_and_last_name(): void
    {
        $contactService = new ContactService;
        $contact = (new Contact)
            ->setFullName(self::FAKE_FULLNAME)
            ->setFirstName(self::FAKE_FIRSTNAME)
            ->setLastName(self::FAKE_LASTNAME);

        $customerXML = $contactService->getCustomerXMLFromContact(self::FAKE_CUSTOMER_ID, $contact);

        $this->assertEquals(self::FAKE_FIRSTNAME, (string) $customerXML->firstname);
        $this->assertEquals(self::FAKE_LASTNAME, (string) $customerXML->surname);
    }
}
