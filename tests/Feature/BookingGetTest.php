<?php

namespace Tests\Feature;

use App\Exceptions\InvalidBookingUUIDException;
use App\Exceptions\NoMatchingDataException;
use App\Exceptions\APICallNotOKException;
use App\Http\Responses\OctoResponse;
use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\LocaleService;
use App\Services\ProductMappingFactory;
use App\Services\ProductService;
use App\Services\RateService;
use App\Services\TourCMSService;
use App\Services\UnitService;
use App\Transformers\BaseTransformer;
use App\Transformers\BookingTransformer;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Tests\FeatureTestCase;

class BookingGetTest extends FeatureTestCase
{
    use WithoutMiddleware;

    const VALID_BOOKING_UUID = '41cb84e7-b4d9-4cb4-809e-cac7a5e5493a';
    const BEARER_TOKEN = '12345|143|ccadca970eea';
    const INVALID_BOOKING_UUID = 'invalid booking id';
    const TCMS_BOOKING_ID = 4093;
    const VALID_PRODUCT_ID = 'TE_1_67|142';
    const VALID_AVAILABILITY_ID = '2024-12-22|32310';
    const VALID_OPTION_ID = 'START_TIME';
    const VALID_UNIT_ITEMS = [
        [
            UnitService::UNIT_ID_FIELD => "TE_1_67|142|r1"
        ],
        [
            UnitService::UNIT_ID_FIELD => "TE_1_67|142|r2" 
        ]
    ];

    public MockObject $bookingServiceMock;
    public BookingTransformer $transformer;
    public SimpleXMLElement $showChannelXML;
    public SimpleXMLElement $showTourXML;
    public SimpleXMLElement $commitBookingXML;
    public SimpleXMLElement $showBookingXML;
    public MockObject $tourCMSServiceMock;
    public MockObject $availabilityServiceMock;
    public ProductService $productService;

    public function setUp(): void
    {
        parent::setUp();
        $this->transformer = new BookingTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->showChannelXML = simplexml_load_file('tests/TourCMSResponses/showChannel.xml');
    }

    public function test_whenBookingUuidIsInvalid_thenExpectsInvalidBookingUuidError(): void
    {
        $this->createBookingServiceMock(['getBookingByUuid']);

        $this->bookingServiceMock
            ->method('getBookingByUuid')
            ->willThrowException(new InvalidBookingUUIDException(self::INVALID_BOOKING_UUID));

        $this->instance(BookingService::class, $this->bookingServiceMock);

        $response = $this->getJson(
            "/bookings/" . self::INVALID_BOOKING_UUID
        );

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $responseData = $response->decodeResponseJson();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_BOOKING_UUID);
        $this->assertEquals($responseData['errorMessage'], OctoResponse::ERROR_MESSAGE_INVALID_BOOKING_UUID);
    }

    public function test_whenBookingNotFound_thenExpectsInvalidBookingUuidError(): void
    {
        $this->createBookingServiceMock(['getBookingByUuid']);

        $this->bookingServiceMock
            ->method('getBookingByUuid')
            ->willThrowException(new NoMatchingDataException());

        $this->instance(BookingService::class, $this->bookingServiceMock);

        $response = $this->getJson(
            "/bookings/" . self::VALID_BOOKING_UUID
        );

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $responseData = $response->decodeResponseJson();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_BOOKING_UUID);
    }

    public function test_whenApiCallFails_thenExpectsInternalServerError(): void
    {
        $this->createBookingServiceMock(['getBookingByUuid']);

        $this->bookingServiceMock
            ->method('getBookingByUuid')
            ->willThrowException(new APICallNotOKException());

        $response = $this->getJson(
            "/bookings/" . self::VALID_BOOKING_UUID
        );

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_whenBookingUuidIsValidAndAPIreturnsTheBooking_thenShouldReturnsBookingObject(): void
    {
        $this->showTourXML = simplexml_load_string(file_get_contents('tests/TourCMSResponses/showTour_67.xml'));
        $this->showBookingXML = simplexml_load_file('tests/TourCMSResponses/showBooking.xml');

        $date = '2024-12-05';
        $expectedTourId = '67';
        $expectedChannelId = '142';

        $this->tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showBooking', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->tourCMSServiceMock
            ->method('showTour')
            ->with($expectedTourId, $expectedChannelId)
            ->willReturn($this->showTourXML);
        
        $this->tourCMSServiceMock
            ->method('showBooking')
            ->with()
            ->willReturn($this->showBookingXML);
        
        $this->tourCMSServiceMock
            ->method('showChannel')
            ->willReturn($this->showChannelXML);


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
            ->onlyMethods([])
            ->getMock();

        $this->availabilityServiceMock->tourCMSService = $this->tourCMSServiceMock;
        
        $this->instance(AvailabilityService::class, $this->availabilityServiceMock);

        $rateServiceMock = $this->getMockBuilder(RateService::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mock ProductService
        $this->productService = new ProductService($this->tourCMSServiceMock, $this->getLoggerMock(), new LocaleService, new ProductMappingFactory, $rateServiceMock);
        $this->instance(ProductService::class, $this->productService);
        
        $getBookingResponse = new Booking();
        $getBookingResponse->setUuid(self::VALID_BOOKING_UUID);
        $getBookingResponse->setBookingId(self::TCMS_BOOKING_ID);
        $getBookingResponse->unit_items = json_encode(self::VALID_UNIT_ITEMS);
        $getBookingResponse->product_id = self::VALID_PRODUCT_ID;
        $getBookingResponse->option_id = self::VALID_OPTION_ID;
        $getBookingResponse->availability_id = self::VALID_AVAILABILITY_ID;
        
        $this->createBookingServiceMock(['getBookingByUuid']);
        $this->bookingServiceMock
            ->method('getBookingByUuid')
            ->willReturnCallback(function(string $uuid) use ($getBookingResponse): Booking {
                if ($uuid == self::VALID_BOOKING_UUID) {
                    return $getBookingResponse;
                }
                throw new InvalidBookingUUIDException($uuid);
            });
        
        $this->bookingServiceMock->tourCMSService = $this->tourCMSServiceMock;
        $this->bookingServiceMock->logger = $this->getLoggerMock();
        $this->bookingServiceMock->productService = $this->productService;
        $this->bookingServiceMock->availabilityService = $this->availabilityServiceMock;
        
        $this->instance(BookingService::class, $this->bookingServiceMock);

        $response = $this->getJson(
            "/bookings/" . self::VALID_BOOKING_UUID
        );

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
    protected function createBookingServiceMock(array $methods): void
    {
        $this->bookingServiceMock = $this->getMockBuilder(BookingService::class)
        ->disableOriginalConstructor()
        ->onlyMethods($methods)
        ->getMock();
    }
}
