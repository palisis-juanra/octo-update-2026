<?php

namespace Tests\Feature;

use App\Http\Controllers\BookingReservationController;
use App\Http\Requests\OctoRequest;
use App\Http\Responses\OctoResponse;
use App\Models\Availability\Availability;
use App\Services\AvailabilityService;
use App\Services\BookingReservationService;
use App\Services\LocaleService;
use App\Services\OptionService;
use App\Services\ProductService;
use App\Services\TourCMSService;
use App\Services\UnitService;
use App\Transformers\BaseTransformer;
use App\Transformers\BookingTransformer;
use Tests\FeatureTestCase;
use Illuminate\Testing\TestResponse;
use SimpleXMLElement;

class BookingReservationTest extends FeatureTestCase
{
    const BOOKINGS_ENDPOINT = '/bookings';
    const VALID_PRODUCT_ID = 'TE_1_67|142';
    const INVALID_PRODUCT_ID = 'invalidProductId';
    const VALID_AVAILABILITY_ID = '2024-12-05|32293';
    const INVALID_AVAILABILITY_ID = 'invalidAvailabilityId';
    const VALID_OPTION_ID = 'START_TIME';
    const INVALID_OPTION_ID = 'invalidOptionId';
    const VALID_UNIT_ITEMS = [
        [
            UnitService::UNIT_ID_FIELD => "TE_1_67|r1"
        ],
        [
            UnitService::UNIT_ID_FIELD => "TE_1_67|r2" 
        ]
    ];

    public SimpleXMLElement $showTourXML;
    public SimpleXMLElement $showTourDeparturesXML;
    public SimpleXMLElement $checkAvailXML;
    public SimpleXMLElement $startNewBookingXML;

    public function setUp(): void
    {
        parent::setUp();

        $this->showTourXML = simplexml_load_string(file_get_contents('tests/TourCMSResponses/showTour_67.xml'));
        $this->showTourDeparturesXML = simplexml_load_string(file_get_contents('tests/TourCMSResponses/showTourDepartures.xml'));
        $this->checkAvailXML = simplexml_load_file('tests/TourCMSResponses/checkAvailability.xml');
        $this->startNewBookingXML = simplexml_load_file('./tests/TourCMSResponses/startNewBooking.xml');
        
        $date = '2024-12-05';
        $expectedParams = 'date=2024-12-05&r1=1&r2=1';

        $expectedTourId = '67';
        $expectedChannelId = '142';
        

        // Mock TourCMSService
        $tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'checkAvailability', 'startNewBooking'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $tourCMSServiceMock
            ->method('showTour')
            ->with($expectedTourId, $expectedChannelId)
            ->willReturn($this->showTourXML);
        
        $tourCMSServiceMock
            ->method('checkAvailability')
            ->with($expectedParams, $expectedTourId)
            ->willReturn($this->checkAvailXML);
        
        $tourCMSServiceMock
            ->method('startNewBooking')
            ->willReturn($this->startNewBookingXML);

        $this->instance(TourCMSService::class, $tourCMSServiceMock);

        // Mock AvailabilityService
        $availability = new Availability();
        $availability->setId(self::VALID_AVAILABILITY_ID);
        $availability->setLocalDateTimeStart($date);
        $availability->setLocalDateTimeEnd($date);
        $availability->setDepartureId(32659);
        $availability->setAllDay(false);
        $availability->setOpeningHoursFrom("00:00");
        $availability->setOpeningHoursTo("23:00");

        $availabilityServiceMock = $this->getMockBuilder(AvailabilityService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();

        $availabilityServiceMock->tourCMSService = $tourCMSServiceMock;

        $availabilityServiceMock
            ->method('find')
            ->willReturn($availability);
        
        $this->instance(AvailabilityService::class, $availabilityServiceMock);

        // Mock ProductService
        $productService = new ProductService($tourCMSServiceMock, $this->getLoggerMock(), new LocaleService);
        $this->instance(ProductService::class, $productService);

        // Mock Controller
        $bookingReservationController = $this->getMockBuilder(BookingReservationController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $bookingReservationController->logger = $this->getLoggerMock();
        $bookingReservationController->productService = $productService;
        $bookingReservationController->availabilityService = $availabilityServiceMock;
        $bookingReservationController->optionService = new OptionService();
        $bookingReservationController->unitService = new UnitService();
        $bookingReservationController->bookingService = new BookingReservationService($tourCMSServiceMock, $productService, $availabilityServiceMock, $this->getLoggerMock());
        $bookingReservationController->transformer = new BookingTransformer(BaseTransformer::FULL_TRANSFORM);
        
        $this->instance(BookingReservationController::class, $bookingReservationController);
    }
    
    public function test_whenRequestDoesNotHaveProductId_thenWeReturnBadRequest(): void
    {
            $body = [
                OctoRequest::AVAILABILITY_ID => self::VALID_AVAILABILITY_ID,
                OctoRequest::OPTION_ID => self::VALID_OPTION_ID,
            ];

            $response = $this->callEndpoint($body);
    
            $responseData = $response->decodeResponseJson();
            $response->assertBadRequest();
            $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_PRODUCT_ID);
            $this->assertEquals($responseData['errorMessage'], OctoResponse::ERROR_MESSAGE_INVALID_PRODUCT_ID);
    }

    public function test_whenRequestHaveEmptyProductId_thenWeReturnBadRequest(): void
    {
            $body = [
                OctoRequest::PRODUCT_ID => '',
                OctoRequest::AVAILABILITY_ID => self::VALID_AVAILABILITY_ID,
                OctoRequest::OPTION_ID => self::VALID_OPTION_ID,
            ];

            $response = $this->callEndpoint($body);
    
            $responseData = $response->decodeResponseJson();
            $response->assertBadRequest();
            $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_PRODUCT_ID);
            $this->assertEquals($responseData['errorMessage'], OctoResponse::ERROR_MESSAGE_INVALID_PRODUCT_ID);
    }

    public function test_whenRequestHaveAnInvalidProductId_thenWeReturnBadRequest(): void
    {
            $body = [
                OctoRequest::PRODUCT_ID => self::INVALID_PRODUCT_ID,
                OctoRequest::AVAILABILITY_ID => self::VALID_AVAILABILITY_ID,
                OctoRequest::OPTION_ID => self::VALID_OPTION_ID,
            ];

            $response = $this->callEndpoint($body);
    
            $responseData = $response->decodeResponseJson();
            $response->assertBadRequest();
            $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_PRODUCT_ID);
            $this->assertEquals($responseData['errorMessage'], OctoResponse::ERROR_MESSAGE_INVALID_PRODUCT_ID);
    }

    public function test_whenRequestDoesNotHaveOptionId_thenWeReturnBadRequest(): void
    {
            $body = [
                OctoRequest::PRODUCT_ID => self::VALID_PRODUCT_ID,
                OctoRequest::AVAILABILITY_ID => self::VALID_AVAILABILITY_ID,
            ];

            $response = $this->callEndpoint($body);
    
            $responseData = $response->decodeResponseJson();
            $response->assertBadRequest();
            $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_OPTION_ID);
            $this->assertEquals($responseData['errorMessage'], OctoResponse::ERROR_MESSAGE_INVALID_OPTION_ID);
    }

    public function test_whenRequestHaveEmptyOptionId_thenWeReturnBadRequest(): void
    {
        
        $body = [
            OctoRequest::PRODUCT_ID => self::VALID_PRODUCT_ID,
            OctoRequest::AVAILABILITY_ID => self::VALID_AVAILABILITY_ID,
            OctoRequest::OPTION_ID => '',
        ];

        $response = $this->callEndpoint($body);

        $responseData = $response->decodeResponseJson();
        $response->assertBadRequest();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_OPTION_ID);
        $this->assertEquals($responseData['errorMessage'], OctoResponse::ERROR_MESSAGE_INVALID_OPTION_ID);
        
    }

    public function test_whenRequestHaveInvalidOptionId_thenWeReturnBadRequest(): void
    {
    
        $body = [
            OctoRequest::PRODUCT_ID => self::VALID_PRODUCT_ID,
            OctoRequest::AVAILABILITY_ID => self::VALID_AVAILABILITY_ID,
            OctoRequest::OPTION_ID => self::INVALID_OPTION_ID,
        ];

        $response = $this->callEndpoint($body);

        $responseData = $response->decodeResponseJson();
        $response->assertBadRequest();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_OPTION_ID);
        $this->assertEquals($responseData['errorMessage'], OctoResponse::ERROR_MESSAGE_INVALID_OPTION_ID);
        
    }

    public function test_whenRequestDoesNotHaveUnitItems_thenWeReturnBadRequest(): void
    {
        $body = [
            OctoRequest::PRODUCT_ID => self::VALID_PRODUCT_ID,
            OctoRequest::AVAILABILITY_ID => self::INVALID_AVAILABILITY_ID,
            OctoRequest::OPTION_ID => self::VALID_OPTION_ID,
        ];

        $response = $this->callEndpoint($body);

        $responseData = $response->decodeResponseJson();
        $response->assertBadRequest();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_UNPROCESSABLE_ENTITY);
        $this->assertEquals($responseData['errorMessage'], UnitService::ERROR_MESSAGE_INVALID_UNIT_ITEMS);
    }

    public function test_whenRequestHaveInvalidUnitItems_thenWeReturnBadRequest(): void
    {
        $body = [
            OctoRequest::PRODUCT_ID => self::VALID_PRODUCT_ID,
            OctoRequest::AVAILABILITY_ID => self::INVALID_AVAILABILITY_ID,
            OctoRequest::OPTION_ID => self::VALID_OPTION_ID,
            OctoRequest::UNIT_ITEMS => []
        ];

        $response = $this->callEndpoint($body);

        $responseData = $response->decodeResponseJson();
        $response->assertBadRequest();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_UNPROCESSABLE_ENTITY);
        $this->assertEquals($responseData['errorMessage'], UnitService::ERROR_MESSAGE_INVALID_UNIT_ITEMS);
    }

    public function test_whenRequestIsCorrect_thenWeReturnBookingInformation(): void
    {
        $body = [
            OctoRequest::PRODUCT_ID => self::VALID_PRODUCT_ID,
            OctoRequest::AVAILABILITY_ID => self::VALID_AVAILABILITY_ID,
            OctoRequest::OPTION_ID => self::VALID_OPTION_ID,
            OctoRequest::UNIT_ITEMS => self::VALID_UNIT_ITEMS
        ];

        // Call endpoint
        $response = $this->callEndpoint($body);

        $responseData = $response->decodeResponseJson();
        $response->assertOk();
    }

    protected function callEndpoint(array $body): TestResponse
    {
        return $this->post(self::BOOKINGS_ENDPOINT, $body, [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
    }
}