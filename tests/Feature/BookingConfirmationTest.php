<?php

namespace Tests\Feature;

use App\Exceptions\InvalidBookingUUIDException;
use App\Http\Responses\OctoResponse;
use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Models\Contact;
use App\Services\AvailabilityService;
use App\Services\BookingConfirmationService;
use App\Services\ContactService;
use App\Services\LocaleService;
use App\Services\ProductMappingFactory;
use App\Services\ProductService;
use App\Services\TourCMSService;
use App\Services\TourPromotionService;
use App\Services\UnitService;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tests\FeatureTestCase;

class BookingConfirmationTest extends FeatureTestCase
{
    const INVALID_BOOKING_UUID = 'invalidUuid';

    const VALID_BOOKING_UUID = '41cb84e7-b4d9-4cb4-809e-cac7a5e5493a';

    const TCMS_BOOKING_ID = 4093;

    const VALID_PRODUCT_ID = 'TE_1_67|142';

    const VALID_AVAILABILITY_ID = '2024-12-22|32310';

    const VALID_OPTION_ID = 'START_TIME';

    const VALID_UNIT_ITEMS = [
        [
            UnitService::UNIT_ID_FIELD => 'TE_1_67|142|r1',
        ],
        [
            UnitService::UNIT_ID_FIELD => 'TE_1_67|142|r2',
        ],
    ];

    const VALID_UNIT_ITEMS_MULTIPLE_UNITS_WITH_SAME_RATE = [
        [
            UnitService::UNIT_ID_FIELD => 'TE_1_67|142|r1',
        ],
        [
            UnitService::UNIT_ID_FIELD => 'TE_1_67|142|r2',
        ],
        [
            UnitService::UNIT_ID_FIELD => 'TE_1_67|142|r2',
        ],
    ];

    const LEAD_MISSING_DATA = 0;

    const LEAD_NO_MISSING_DATA = 1;

    const LEAD_INFO_MATCHES_OTHER_PASSENGER = 2;

    public SimpleXMLElement $showChannelXML;

    public SimpleXMLElement $showTourXML;

    public SimpleXMLElement $commitBookingXML;

    public SimpleXMLElement $showBookingXML;

    public MockObject $tourCMSServiceMock;

    public MockObject $availabilityServiceMock;

    public MockObject $bookingConfirmationServiceMock;

    public ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_when_booking_uuid_is_invalid_then_expects_invalid_booking_uuid_error(): void
    {
        $this->mockServices('tests/TourCMSResponses/showBooking.xml');
        $response = $this->post('/bookings/'.self::INVALID_BOOKING_UUID.'/confirm', [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
        $response->assertBadRequest();
        $responseData = $response->decodeResponseJson();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_BOOKING_UUID);
        $this->assertEquals($responseData['errorMessage'], OctoResponse::ERROR_MESSAGE_INVALID_BOOKING_UUID);
    }

    public function test_when_booking_uuid_is_correct_then_we_can_confirm_the_booking(): void
    {
        $this->mockServices('tests/TourCMSResponses/showBooking.xml');
        $response = $this->post('/bookings/'.self::VALID_BOOKING_UUID.'/confirm', [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
        $response->assertOk();

        $responseData = $response->decodeResponseJson();
        $responseData->assertFragment([
            'id' => '1|142|4093',
            'uuid' => self::VALID_BOOKING_UUID,
            'status' => Booking::STATUS_CONFIRMED,
            'productId' => self::VALID_PRODUCT_ID,
            'availabilityId' => self::VALID_AVAILABILITY_ID,
            'optionId' => self::VALID_OPTION_ID,
        ]);
    }

    public function test_when_booking_has_tickets_then_we_get_it_on_response()
    {
        $this->mockServices('tests/TourCMSResponses/showBookingWithTickets.xml');
        $response = $this->post('/bookings/'.self::VALID_BOOKING_UUID.'/confirm', [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
        $response->assertOk();

        $response->assertJsonPath('unitItems.1.ticket.deliveryOptions.0.deliveryValue', '10246817');
        $response->assertJsonPath('unitItems.1.ticket.deliveryOptions.0.deliveryFormat', 'CODE128');
        $response->assertJsonPath('unitItems.1.ticket.redemptionMethod', 'DIGITAL');
        $response->assertJsonPath('unitItems.1.ticket.utcRedeemedAt', null);
    }

    public function test_when_travellers_information_is_sent_then_we_get_it_on_response(): void
    {
        $this->mockServices('tests/TourCMSResponses/showBookingWithAssociatedTravellers.xml');
        $response = $this->post('/bookings/'.self::VALID_BOOKING_UUID.'/confirm', [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
        $response->assertOk();

        $i = 0;
        foreach ($this->showBookingXML->booking->customers->customer as $customer) {
            $response->assertJsonPath('unitItems.'.$i.'.contact.firstName', (string) $customer->firstname);
            $response->assertJsonPath('unitItems.'.$i.'.contact.lastName', (string) $customer->surname);
            $response->assertJsonPath('unitItems.'.$i.'.contact.emailAddress', (string) $customer->customer_email);
            $response->assertJsonPath('unitItems.'.$i.'.contact.phoneNumber', (string) $customer->customer_tel_mobile);
            $response->assertJsonPath('unitItems.'.$i.'.contact.postalCode', (string) $customer->postcode);
            $response->assertJsonPath('unitItems.'.$i.'.contact.country', (string) $customer->country);
            $response->assertJsonPath('unitItems.'.$i.'.contact.notes', (string) $customer->customer_contact_note);
            $i++;
        }
    }

    public function test_when_add_contact_to_booking_and_already_fulfilled_new_lead_data_then_contact_details_are_missing_in_the_booking(): void
    {
        $this->mockServices(
            'tests/TourCMSResponses/showTemporaryBookingMultipleTravelers.xml',
            'tests/TourCMSResponses/showBookingMultipleTravellersAndFulfilledLead.xml',
            true
        );

        $mockedCustomerData = $this->getMockedDataFromRequest(self::LEAD_NO_MISSING_DATA);

        $call = 0;

        $this->bookingConfirmationServiceMock
            ->expects($this->exactly(4))
            ->method('updateTraveller')
            ->willReturnCallback(function (int $customerId, Contact $contact) use (&$call, $mockedCustomerData) {
                switch ($call++) {
                    case 0:
                        $this->assertEquals(13851, $customerId);
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][0]['contact']['fullName'],
                            $contact->getFullName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][0]['contact']['firstName'],
                            $contact->getFirstName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][0]['contact']['lastName'],
                            $contact->getLastName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][0]['contact']['emailAddress'],
                            $contact->getEmailAddress()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][0]['contact']['notes'],
                            $contact->getNotes()
                        );
                        break;

                    case 1:
                        $this->assertEquals(13852, $customerId);
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['fullName'],
                            $contact->getFullName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['firstName'],
                            $contact->getFirstName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['lastName'],
                            $contact->getLastName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['emailAddress'],
                            $contact->getEmailAddress()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['notes'],
                            $contact->getNotes()
                        );
                        break;

                    case 2:
                        $this->assertEquals(13853, $customerId);
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['fullName'],
                            $contact->getFullName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['firstName'],
                            $contact->getFirstName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['lastName'],
                            $contact->getLastName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['emailAddress'],
                            $contact->getEmailAddress()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['notes'],
                            $contact->getNotes()
                        );
                        break;

                    case 3:
                        $this->assertEquals(13851, $customerId);
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['fullName'],
                            $contact->getFullName()
                        );
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['firstName'],
                            $contact->getFirstName()
                        );
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['lastName'],
                            $contact->getLastName()
                        );
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['emailAddress'],
                            $contact->getEmailAddress()
                        );
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['notes'],
                            $contact->getNotes()
                        );
                        break;
                }

                return true;
            });

        $response = $this->post(
            '/bookings/'.self::VALID_BOOKING_UUID.'/confirm',
            $mockedCustomerData,
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertOk();
    }

    public function test_when_new_lead_pax_info_matches_other_traveler_then_we_get_both_in_response(): void
    {
        $this->mockServices(
            'tests/TourCMSResponses/showTemporaryBookingMultipleTravelers.xml',
            'tests/TourCMSResponses/showBookingMultipleTravellersAndFulfilledLead.xml',
            true
        );

        $mockedCustomerData = $this->getMockedDataFromRequest(self::LEAD_INFO_MATCHES_OTHER_PASSENGER);

        $call = 0;

        $this->bookingConfirmationServiceMock
            ->expects($this->exactly(4))
            ->method('updateTraveller')
            ->willReturnCallback(function (int $customerId, Contact $contact) use (&$call, $mockedCustomerData) {
                switch ($call++) {
                    case 0:
                        $this->assertEquals(13851, $customerId);
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['fullName'],
                            $contact->getFullName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['firstName'],
                            $contact->getFirstName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['lastName'],
                            $contact->getLastName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['emailAddress'],
                            $contact->getEmailAddress()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['notes'],
                            $contact->getNotes()
                        );
                        break;

                    case 1:
                        $this->assertEquals(13852, $customerId);
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['fullName'],
                            $contact->getFullName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['firstName'],
                            $contact->getFirstName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['lastName'],
                            $contact->getLastName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['emailAddress'],
                            $contact->getEmailAddress()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['notes'],
                            $contact->getNotes()
                        );
                        break;

                    case 2:
                        $this->assertEquals(13853, $customerId);
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['fullName'],
                            $contact->getFullName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['firstName'],
                            $contact->getFirstName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['lastName'],
                            $contact->getLastName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['emailAddress'],
                            $contact->getEmailAddress()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['notes'],
                            $contact->getNotes()
                        );
                        break;

                    case 3:
                        $this->assertEquals(13851, $customerId);
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['fullName'],
                            $contact->getFullName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['firstName'],
                            $contact->getFirstName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['lastName'],
                            $contact->getLastName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['emailAddress'],
                            $contact->getEmailAddress()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['notes'],
                            $contact->getNotes()
                        );
                        break;
                }

                return true;
            });

        $response = $this->post(
            '/bookings/'.self::VALID_BOOKING_UUID.'/confirm',
            $mockedCustomerData,
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertOk();
    }

    public function test_when_new_lead_pax_is_missing_data_then_we_get_it_on_response_from_original_contact(): void
    {
        $this->mockServices(
            'tests/TourCMSResponses/showTemporaryBookingMultipleTravelers.xml',
            'tests/TourCMSResponses/showBookingMultipleTravellersAndFulfilledLead.xml',
            true
        );

        $mockedCustomerData = $this->getMockedDataFromRequest(self::LEAD_MISSING_DATA);

        $call = 0;

        $this->bookingConfirmationServiceMock
            ->expects($this->exactly(4))
            ->method('updateTraveller')
            ->willReturnCallback(function (int $customerId, Contact $contact) use (&$call, $mockedCustomerData) {
                switch ($call++) {
                    case 0:
                        $this->assertEquals(13851, $customerId);
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['fullName'],
                            $contact->getFullName()
                        );
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['firstName'],
                            $contact->getFirstName()
                        );
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['lastName'],
                            $contact->getLastName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['contact']['emailAddress'],
                            $contact->getEmailAddress()
                        );
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['notes'],
                            $contact->getNotes()
                        );
                        break;

                    case 1:
                        $this->assertEquals(13852, $customerId);
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['fullName'],
                            $contact->getFullName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['firstName'],
                            $contact->getFirstName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['lastName'],
                            $contact->getLastName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['emailAddress'],
                            $contact->getEmailAddress()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][1]['contact']['notes'],
                            $contact->getNotes()
                        );
                        break;

                    case 2:
                        $this->assertEquals(13853, $customerId);
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['fullName'],
                            $contact->getFullName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['firstName'],
                            $contact->getFirstName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['lastName'],
                            $contact->getLastName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['emailAddress'],
                            $contact->getEmailAddress()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['unitItems'][2]['contact']['notes'],
                            $contact->getNotes()
                        );
                        break;

                    case 3:
                        $this->assertEquals(13851, $customerId);
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['fullName'],
                            $contact->getFullName()
                        );
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['firstName'],
                            $contact->getFirstName()
                        );
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['lastName'],
                            $contact->getLastName()
                        );
                        $this->assertEquals(
                            $mockedCustomerData['contact']['emailAddress'],
                            $contact->getEmailAddress()
                        );
                        $this->assertNotEquals(
                            $mockedCustomerData['contact']['notes'],
                            $contact->getNotes()
                        );
                        break;
                }

                return true;
            });

        $response = $this->post(
            '/bookings/'.self::VALID_BOOKING_UUID.'/confirm',
            $mockedCustomerData,
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertOk();
    }

    public function test_when_component_does_not_have_ticket_or_url_then_we_default_tourcms_barcode_value(): void
    {
        $this->mockServices('tests/TourCMSResponses/showBookingWithOneUrlPerComponent.xml');
        $response = $this->post('/bookings/'.self::VALID_BOOKING_UUID.'/confirm', [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
        $response->assertOk();

        $response->assertJsonPath('unitItems.0.ticket.deliveryOptions.0.deliveryValue', (string) $this->showBookingXML->booking->barcode_data);
        $response->assertJsonPath('unitItems.1.ticket.deliveryOptions.0.deliveryValue', (string) $this->showBookingXML->booking->barcode_data);
    }

    // PROTECTED METHODS

    protected function mockServices(string $showBookingFile, string $commitBookingFile = 'tests/TourCMSResponses/commitBooking.xml', bool $multipleUnitsSameRate = false): void
    {
        $this->showChannelXML = simplexml_load_file('tests/TourCMSResponses/showChannel.xml');
        $this->showTourXML = simplexml_load_file('tests/TourCMSResponses/showTour_67.xml');
        $this->commitBookingXML = simplexml_load_file($commitBookingFile);
        $this->showBookingXML = simplexml_load_file($showBookingFile);

        $date = '2024-12-05';

        $expectedTourId = '67';
        $expectedChannelId = '142';

        // Mock TourCMSService
        $this->tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showChannel', 'showTour', 'commitBooking', 'showBooking', 'callTourCMSAddNoteToBooking', 'updateCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->tourCMSServiceMock
            ->method('showChannel')
            ->willReturn($this->showChannelXML);

        $this->tourCMSServiceMock
            ->method('showTour')
            ->with($expectedTourId, $expectedChannelId)
            ->willReturn($this->showTourXML);

        $this->tourCMSServiceMock
            ->method('commitBooking')
            ->willReturn($this->commitBookingXML);

        $this->tourCMSServiceMock
            ->method('showBooking')
            ->with()
            ->willReturn($this->showBookingXML);

        $this->tourCMSServiceMock
            ->method('updateCustomer')
            ->willReturnCallback(fn ($customer) => $customer);

        $this->instance(TourCMSService::class, $this->tourCMSServiceMock);

        // Mock Availability and AvailabilityService
        $availability = new Availability;
        $availability->setId(self::VALID_AVAILABILITY_ID);
        $availability->setLocalDateTimeStart($date);
        $availability->setLocalDateTimeEnd($date);
        $availability->setDepartureId(32659);
        $availability->setAllDay(false);
        $availability->setOpeningHoursFrom('00:00');
        $availability->setOpeningHoursTo('23:00');

        $this->availabilityServiceMock = $this->getMockBuilder(AvailabilityService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->availabilityServiceMock->tourCMSService = $this->tourCMSServiceMock;

        $this->instance(AvailabilityService::class, $this->availabilityServiceMock);

        $tourPromotionServiceMock = $this->getMockBuilder(TourPromotionService::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mock ProductService
        $this->productService = new ProductService($this->tourCMSServiceMock, $this->getLoggerMock(), new LocaleService, new ProductMappingFactory, $tourPromotionServiceMock);
        $this->instance(ProductService::class, $this->productService);

        $this->bookingConfirmationServiceMock = $this->getMockBuilder(BookingConfirmationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBookingByUuid', 'confirmBooking', 'updateTraveller'])
            ->getMock();

        $this->bookingConfirmationServiceMock
            ->method('confirmBooking')
            ->willReturnCallback(fn ($booking) => $booking);

        $booking = new Booking;
        $booking->setUuid(self::VALID_BOOKING_UUID);
        $booking->setBookingId(self::TCMS_BOOKING_ID);
        $booking->unit_items = json_encode(self::VALID_UNIT_ITEMS);

        if ($multipleUnitsSameRate) {
            $booking->unit_items = json_encode(self::VALID_UNIT_ITEMS_MULTIPLE_UNITS_WITH_SAME_RATE);
        }

        $booking->product_id = self::VALID_PRODUCT_ID;
        $booking->option_id = self::VALID_OPTION_ID;
        $booking->availability_id = self::VALID_AVAILABILITY_ID;

        $this->bookingConfirmationServiceMock
            ->method('getBookingByUuid')
            ->willReturnCallback(function (string $uuid) use ($booking): Booking {
                if ($uuid == self::VALID_BOOKING_UUID) {
                    return $booking;
                }
                throw new InvalidBookingUUIDException($uuid);
            });

        $this->bookingConfirmationServiceMock->tourCMSService = $this->tourCMSServiceMock;
        $this->bookingConfirmationServiceMock->logger = $this->getLoggerMock();
        $this->bookingConfirmationServiceMock->productService = $this->productService;
        $this->bookingConfirmationServiceMock->availabilityService = $this->availabilityServiceMock;
        $this->bookingConfirmationServiceMock->contactService = new ContactService;

        $this->instance(BookingConfirmationService::class, $this->bookingConfirmationServiceMock);
    }

    private function getMockedDataFromRequest(int $leadType, ?string $resellerReference = null): array
    {
        $contact = [
            'firstName' => 'Mick',
            'lastName' => 'Jackson',
            'emailAddress' => 'mj@gmail.com',
            'fullName' => 'Mick Jackson',
            'notes' => 'Test note',
        ];

        $unitItem1 = [
            'unitId' => 'TE_1_67|142|r1',
            'contact' => [
                'firstName' => 'John',
                'lastName' => '',
                'emailAddress' => '',
                'fullName' => 'John Doe',
                'notes' => 'Test note1',
            ],
        ];

        $unitItem2 = [
            'unitId' => 'TE_1_67|142|r1',
            'contact' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emailAddress' => 'jd@gmail.com',
                'fullName' => 'John Doe',
                'notes' => 'Test note1',
            ],
        ];

        $unitItem3 = [
            'unitId' => 'TE_1_67|142|r2',
            'contact' => [
                'firstName' => 'Rebecca',
                'lastName' => 'Doe',
                'emailAddress' => 'rd@gmail.com',
                'fullName' => 'Rebecca Doe',
                'notes' => 'Test note3',
            ],
        ];

        $unitItem4 = [
            'unitId' => 'TE_1_67|142|r2',
            'contact' => [
                'firstName' => 'Monica',
                'lastName' => 'Orange',
                'emailAddress' => 'mo@gmail.com',
                'fullName' => 'Monica Orange',
                'notes' => 'Test note4',
            ],
        ];

        $unitItem5 = [
            'unitId' => 'TE_1_67|142|r1',
            'contact' => [
                'firstName' => 'Rebecca',
                'lastName' => 'Doe',
                'emailAddress' => 'rd@gmail.com',
                'fullName' => 'Rebecca Doe',
                'notes' => 'Test note3',
            ],
        ];

        $unitItems = [$unitItem1, $unitItem3, $unitItem4];

        if ($leadType === self::LEAD_NO_MISSING_DATA) {
            $unitItems = [$unitItem2, $unitItem3, $unitItem4];
        } elseif ($leadType === self::LEAD_INFO_MATCHES_OTHER_PASSENGER) {
            $unitItems = [$unitItem5, $unitItem3, $unitItem4];
        }

        $fulfilledIncomingData = [
            'contact' => $contact,
            'resellerReference' => $resellerReference,
            'unitItems' => $unitItems,
        ];

        return $fulfilledIncomingData;
    }
}
