<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Contact;
use App\Services\BookingConfirmationService;
use App\Services\ContactService;
use App\Services\TourCMSService;
use Tests\UnitTestCase;

class BookingConfirmationServiceTest extends UnitTestCase
{
    const FAKE_CUSTOMER_ID = 12345;
    const CONTACT_DATA = '{"fullName": "Armando Garrido", "firstName": "Armando", "lastName": "Garrido", "emailAddress": "armando.garrido@palisis.com", "phoneNumber": "77777777", "postalCode": "18200", "country": "ES", "notes": "Customer notes"}';

    public function test_whenAddContactToBooking_thenContactDetaiIsArePresentInTheBooking(): void
    {
        $tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateCustomer'])
            ->getMock();
        
        $updateCustomerXML = simplexml_load_string('<error>OK</error>');
        $tourCMSServiceMock
            ->method('updateCustomer')
            ->willReturn($updateCustomerXML);

        $bookingConfirmationService = $this->getMockBuilder(BookingConfirmationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $bookingConfirmationService->tourCMSService = $tourCMSServiceMock;
        $bookingConfirmationService->logger = $this->getLoggerMock();
        $bookingConfirmationService->contactService = new ContactService();

        $fakeBooking = new Booking();
        $fakeBooking->setLeadCustomerId(self::FAKE_CUSTOMER_ID);
        $contactDetails = json_decode(self::CONTACT_DATA, 1);

        $bookingConfirmationService->addContactToBooking($contactDetails, $fakeBooking);
        $contact = $fakeBooking->getContact();

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertEquals($contactDetails['fullName'], $contact->getFullName());
        $this->assertEquals($contactDetails['firstName'], $contact->getFirstName());
        $this->assertEquals($contactDetails['lastName'], $contact->getLastName());
        $this->assertEquals($contactDetails['emailAddress'], $contact->getEmailAddress());
        $this->assertEquals($contactDetails['phoneNumber'], $contact->getPhoneNumber());
        $this->assertEquals($contactDetails['postalCode'], $contact->getPostalCode());
        $this->assertEquals($contactDetails['country'], $contact->getCountry());
        $this->assertEquals($contactDetails['notes'], $contact->getNotes());
    }
}