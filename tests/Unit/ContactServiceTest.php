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


    public function test_whenCreatingCustomerXMLFromContactWithFullName_thenWeGetCustomerXMLWithFistnameFilled(): void
    {
        $contactService = new ContactService();
        $contact = (new Contact())->setFullName(self::FAKE_FULLNAME);

        $customerXML = $contactService->getCustomerXMLFromContact(self::FAKE_CUSTOMER_ID, $contact);
        
        $this->assertEquals(self::FAKE_FULLNAME, (string) $customerXML->firstname);
    }

    public function test_whenCreatingCustomerXMLFromContactWithFullNameAndFirstnameAndSurname_thenWeGetCustomerNameFromFirstnameAndLastName(): void
    {
        $contactService = new ContactService();
        $contact = (new Contact())
            ->setFullName(self::FAKE_FULLNAME)
            ->setFirstName(self::FAKE_FIRSTNAME)
            ->setLastName(self::FAKE_LASTNAME);

        $customerXML = $contactService->getCustomerXMLFromContact(self::FAKE_CUSTOMER_ID, $contact);
        
        $this->assertEquals(self::FAKE_FIRSTNAME, (string) $customerXML->firstname);
        $this->assertEquals(self::FAKE_LASTNAME, (string) $customerXML->surname);
    }

}