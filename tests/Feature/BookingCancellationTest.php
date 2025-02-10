<?php

namespace Tests\Feature;

use App\Exceptions\InvalidBookingUUIDException;
use App\Http\Responses\OctoResponse;
use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\BookingCancellationService;
use App\Services\LocaleService;
use App\Services\ProductService;
use App\Services\TourCMSService;
use App\Services\UnitService;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tests\FeatureTestCase;

class BookingCancellationTest extends FeatureTestCase
{
    const INVALID_BOOKING_UUID = '0j9wdajdwa-09ad-9wdf-0j0k-0jhdw28hd';
    const VALID_BOOKING_UUID = '41cb84e7-b4d9-4cb4-809e-cac7a5e5493a';
    const VALID_BOOKING_ID = 4093;
    const VALID_BOOKING_OBJECT_ID = '1|143|3899';
    const VALID_PRODUCT_ID = 'TE_1_67|142';
    const VALID_AVAILABILITY_ID = '2024-12-30|32433';
    const VALID_OPTION_ID = 'START_TIME';
    const VALID_UNIT_ITEMS = [
        [
            UnitService::UNIT_ID_FIELD => "TE_1_67|r1"
        ],
        [
            UnitService::UNIT_ID_FIELD => "TE_1_67|r2" 
        ]
    ];

    public SimpleXMLElement $showChannelXML;
    public SimpleXMLElement $showTourXML;
    public SimpleXMLElement $cancelBookingXML;
    public SimpleXMLElement $showBookingXML;
    public SimpleXMLElement $showBookingCancelledXML;
    public SimpleXMLElement $deleteBookingXML;
    public MockObject $tourCMSService;
    public MockObject $availabilityService;
    public MockObject $bookingCancellationService;
    public ProductService $productService;

    public function setUp(): void
    {
        parent::setUp();

        $this->showChannelXML = simplexml_load_file('tests/TourCMSResponses/showTour_67.xml');
        $this->showTourXML = simplexml_load_file('tests/TourCMSResponses/showTour_67.xml');
        $this->cancelBookingXML = simplexml_load_string('<response><request>POST /c/booking/cancel.xml</request><error>OK</error></response>');
        $this->showBookingXML = simplexml_load_file('tests/TourCMSResponses/showBooking.xml');
        $this->showBookingCancelledXML = simplexml_load_file('tests/TourCMSResponses/showBookingCancelled.xml');
        $this->deleteBookingXML = simplexml_load_file('tests/TourCMSResponses/deleteBooking.xml');
        
        $date = '2024-12-05';

        $expectedTourId = '67';
        $expectedChannelId = '142';

        $this->tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showChannel', 'showTour', 'cancelBooking', 'showBooking', 'deleteBooking'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->tourCMSService
            ->method('showChannel')
            ->willReturn($this->showChannelXML);
            
        $this->tourCMSService
            ->method('showTour')
            ->with($expectedTourId, $expectedChannelId)
            ->willReturn($this->showTourXML);
        
        $this->tourCMSService
            ->method('cancelBooking')
            ->willReturn($this->cancelBookingXML);
        
        $this->tourCMSService
            ->method('showBooking')
            ->with()
            ->willReturn($this->showBookingCancelledXML);
    
        $this->tourCMSService
            ->method('deleteBooking')
            ->with()
            ->willReturn($this->deleteBookingXML);
        

        $this->instance(TourCMSService::class, $this->tourCMSService);

        $availability = new Availability();
        $availability->setId(self::VALID_AVAILABILITY_ID);
        $availability->setLocalDateTimeStart($date);
        $availability->setLocalDateTimeEnd($date);
        $availability->setDepartureId(32659);
        $availability->setAllDay(false);
        $availability->setOpeningHoursFrom("00:00");
        $availability->setOpeningHoursTo("23:00");

        $this->availabilityService = $this->getMockBuilder(AvailabilityService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();

        $this->availabilityService->tourCMSService = $this->tourCMSService;

        $this->availabilityService
            ->method('find')
            ->with(self::VALID_AVAILABILITY_ID)
            ->willReturn($availability);
        
        $this->instance(AvailabilityService::class, $this->availabilityService);

        $this->productService = new ProductService($this->tourCMSService, $this->getLoggerMock(), new LocaleService);
        $this->instance(ProductService::class, $this->productService);


        $this->bookingCancellationService = $this->getMockBuilder(BookingCancellationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBookingByUuid'])
            ->getMock();
        
        $booking = new Booking();
        $booking->setUuid(self::VALID_BOOKING_UUID);
        $booking->setBookingId(self::VALID_BOOKING_ID);
        $booking->unit_items = json_encode(self::VALID_UNIT_ITEMS);
        $booking->product_id = self::VALID_PRODUCT_ID;
        $booking->option_id = self::VALID_OPTION_ID;
        $booking->availability_id = self::VALID_AVAILABILITY_ID;
        
        $this->bookingCancellationService
            ->method('getBookingByUuid')
            ->willReturnCallback(function(string $uuid) use ($booking): Booking {
                if ($uuid == self::VALID_BOOKING_UUID) {
                    return $booking;
                }
                throw new InvalidBookingUUIDException($uuid);
            });


        $this->bookingCancellationService->tourCMSService = $this->tourCMSService;
        $this->bookingCancellationService->logger = $this->getLoggerMock();
        $this->bookingCancellationService->productService = $this->productService;
        $this->bookingCancellationService->availabilityService = $this->availabilityService;
    
        $this->instance(BookingCancellationService::class, $this->bookingCancellationService);
    }

    public function test_whenBookingUuidIsInvalid_thenExpectsInvalidBookingUuidError(): void
    {
        $response = $this->post("/bookings/". self::INVALID_BOOKING_UUID ."/cancel", [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
        
        $response->assertBadRequest();
        
        $responseData = $response->decodeResponseJson();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_BOOKING_UUID);
        $this->assertEquals($responseData['errorMessage'], OctoResponse::ERROR_MESSAGE_INVALID_BOOKING_UUID);
    }

    public function test_whenBookingUuidIsCorrect_thenWeCanCancelTheBooking(): void
    {
        $response = $this->post("/bookings/". self::VALID_BOOKING_UUID ."/cancel", [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);

        $response->assertOk();

        $responseData = $response->decodeResponseJson();
        $responseData->assertFragment([
            "id" => self::VALID_BOOKING_OBJECT_ID,
            "uuid" => self::VALID_BOOKING_UUID,
            "status" => Booking::STATUS_CANCELLED,
            "productId" => self::VALID_PRODUCT_ID,
            "availabilityId" => self::VALID_AVAILABILITY_ID,
            "optionId" => self::VALID_OPTION_ID
        ]);
    }
}