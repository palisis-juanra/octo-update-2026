<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Contact;
use App\Services\BookingConfirmationService;
use App\Services\ContactService;
use App\Services\JSONLogService;
use App\Services\TourCMSService;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\UnitTestCase;

class BookingConfirmationServiceTest extends UnitTestCase
{
    const FAKE_CUSTOMER_ID = 12345;

    const CONTACT_DATA = '{"fullName": "Armando Garrido", "firstName": "Armando", "lastName": "Garrido", "emailAddress": "armando.garrido@palisis.com", "phoneNumber": "77777777", "postalCode": "18200", "country": "ES", "notes": "Customer notes"}';

    public function test_when_add_contact_to_booking_then_contact_detai_is_are_present_in_the_booking(): void
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
        $bookingConfirmationService->contactService = new ContactService;

        $contactDetails = json_decode(self::CONTACT_DATA, 1);

        $contact = Contact::create($contactDetails);
        $bookingConfirmationService->updateTraveller(self::FAKE_CUSTOMER_ID, $contact);

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

    public function test_when_unit_items_changed_then_we_throw_an_exception(): void
    {
        $reservationUnitItems = [
            [
                'unitId' => 'TE_1_231|r1',
            ],
        ];

        $confirmationUnitItems = [
            [
                'unitId' => 'TE_1_231|r1',
            ],
            [
                'unitId' => 'TE_1_231|r1',
            ],
        ];

        $this->expectException(UnprocessableEntityHttpException::class);

        $bookingConfirmationService = $this->getMockBuilder(BookingConfirmationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $bookingConfirmationService->logger = $this->getMockBuilder(JSONLogService::class)->disableOriginalConstructor()->getMock();

        $booking = new Booking;
        $booking->unit_items = json_encode($reservationUnitItems);

        $bookingConfirmationService->checkUnitItemsHaveNotChanged($booking, $confirmationUnitItems);
    }

    public function test_when_unit_items_remains_the_same_then_we_dont_throw_an_exception(): void
    {
        $reservationUnitItems = [
            [
                'unitId' => 'TE_1_231|r1',
            ],
        ];

        $confirmationUnitItems = [
            [
                'unitId' => 'TE_1_231|r1',
            ],
        ];

        $this->expectNotToPerformAssertions();

        $bookingConfirmationService = $this->getBookingConfirmationService();

        $booking = new Booking;
        $booking->unit_items = json_encode($reservationUnitItems);

        $bookingConfirmationService->checkUnitItemsHaveNotChanged($booking, $confirmationUnitItems);
    }

    protected function getBookingConfirmationService()
    {
        $bookingConfirmationService = $this->getMockBuilder(BookingConfirmationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $bookingConfirmationService->logger = $this->getMockBuilder(JSONLogService::class)->disableOriginalConstructor()->getMock();

        return $bookingConfirmationService;
    }
}
