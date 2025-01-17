<?php

namespace Tests\Feature;

use App\Exceptions\InvalidBookingUUIDException;
use App\Http\Responses\OctoResponse;
use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\BookingConfirmationService;
use App\Services\LocaleService;
use App\Services\ProductService;
use App\Services\TourCMSService;
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
            UnitService::UNIT_ID_FIELD => "TE_1_67|r1"
        ],
        [
            UnitService::UNIT_ID_FIELD => "TE_1_67|r2" 
        ]
    ];

    public SimpleXMLElement $showTourXML;
    public SimpleXMLElement $commitBookingXML;
    public SimpleXMLElement $showBookingXML;
    public MockObject $tourCMSServiceMock;
    public MockObject $availabilityServiceMock;
    public MockObject $bookingConfirmationServiceMock;
    public ProductService $productService;

    public function setUp(): void
    {
        parent::setUp();

        $this->showTourXML = simplexml_load_string(file_get_contents('tests/TourCMSResponses/showTour_67.xml'));
        $this->commitBookingXML = simplexml_load_file('tests/TourCMSResponses/commitBooking.xml');
        $this->showBookingXML = simplexml_load_file('tests/TourCMSResponses/showBooking.xml');
        
        $date = '2024-12-05';

        $expectedTourId = '67';
        $expectedChannelId = '142';
        
        // Mock TourCMSService
        $this->tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'commitBooking', 'showBooking'])
            ->disableOriginalConstructor()
            ->getMock();
        
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

        $this->instance(TourCMSService::class, $this->tourCMSServiceMock);

        // Mock Availability and AvailabilityService
        $availability = new Availability();
        $availability->setId(self::VALID_AVAILABILITY_ID);
        $availability->setLocalDateTimeStart($date);
        $availability->setLocalDateTimeEnd($date);
        $availability->setDepartureId(32659);
        $availability->setAllDay(false);
        $availability->setOpeningHoursFrom("00:00");
        $availability->setOpeningHoursTo("23:00");

        $this->availabilityServiceMock = $this->getMockBuilder(AvailabilityService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();

        $this->availabilityServiceMock->tourCMSService = $this->tourCMSServiceMock;

        $this->availabilityServiceMock
            ->method('find')
            ->with(self::VALID_AVAILABILITY_ID)
            ->willReturn($availability);
        
        $this->instance(AvailabilityService::class, $this->availabilityServiceMock);

        // Mock ProductService
        $this->productService = new ProductService($this->tourCMSServiceMock, $this->getLoggerMock(), new LocaleService);
        $this->instance(ProductService::class, $this->productService);


        $this->bookingConfirmationServiceMock = $this->getMockBuilder(BookingConfirmationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBookingByUuid'])
            ->getMock();
        
        $booking = new Booking();
        $booking->setUuid(self::VALID_BOOKING_UUID);
        $booking->setBookingId(self::TCMS_BOOKING_ID);
        $booking->unit_items = json_encode(self::VALID_UNIT_ITEMS);
        $booking->product_id = self::VALID_PRODUCT_ID;
        $booking->option_id = self::VALID_OPTION_ID;
        $booking->availability_id = self::VALID_AVAILABILITY_ID;
        
        $this->bookingConfirmationServiceMock
            ->method('getBookingByUuid')
            ->willReturnCallback(function(string $uuid) use ($booking): Booking {
                if ($uuid == self::VALID_BOOKING_UUID) {
                    return $booking;
                }
                throw new InvalidBookingUUIDException($uuid);
            });

        $this->bookingConfirmationServiceMock->tourCMSService = $this->tourCMSServiceMock;
        $this->bookingConfirmationServiceMock->logger = $this->getLoggerMock();
        $this->bookingConfirmationServiceMock->productService = $this->productService;
        $this->bookingConfirmationServiceMock->availabilityService = $this->availabilityServiceMock;
    
        $this->instance(BookingConfirmationService::class, $this->bookingConfirmationServiceMock);
    }

    public function test_whenBookingUuidIsInvalid_thenExpectsInvalidBookingUuidError(): void
    {
        $response = $this->post("/bookings/". self::INVALID_BOOKING_UUID ."/confirm", [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);

        
        $response->assertBadRequest();
        
        $responseData = $response->decodeResponseJson();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_BOOKING_UUID);
        $this->assertEquals($responseData['errorMessage'], OctoResponse::ERROR_MESSAGE_INVALID_BOOKING_UUID);
    }

    public function test_whenBookingUuidIsCorrect_thenWeCanConfirmTheBooking(): void
    {
        $response = $this->post("/bookings/". self::VALID_BOOKING_UUID ."/confirm", [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
        $response->assertOk();

        $responseData = $response->decodeResponseJson();
        $responseData->assertFragment([
            "id" => "1|142|4093",
            "uuid" => self::VALID_BOOKING_UUID,
            "status" => Booking::STATUS_CONFIRMED,
            "productId" => self::VALID_PRODUCT_ID,
            "availabilityId" => self::VALID_AVAILABILITY_ID,
            "optionId" => self::VALID_OPTION_ID
        ]);
    }
}