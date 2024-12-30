<?php

namespace Tests\Unit;

use App\Exceptions\AvailabilityRequestInvalidParamException;
use App\Exceptions\AvailabilityRequestMissingParamException;
use App\Models\Availability\AvailabilityCalendarRequest;
use App\Services\AvailabilityCalendarService;
use App\Services\ProductService;
use App\Services\TourCMSService;
use SimpleXMLElement;
use App\Services\OptionService;
use Tests\UnitTestCase;

class AvailabilityCalendarServiceTest extends UnitTestCase
{
    const DATES_AND_DEALS_XML_COUNT = 6;
    const VALID_CHANNEL = '123';
    const VALID_PRODUCT_ID = 'TE_1_2|143';
    const VALID_OPTION_ID = 'START_TIME|13:00';
    const VALID_LOCAL_DATE_START = '2024-11-28';
    const VALID_LOCAL_DATE_END = '2024-11-30';
    const INVALID_LOCAL_DATE_START = '2024-13-28';
    const INVALID_LOCAL_DATE_END = '2024-11-32';
    public string $datesAndDealsString;
    public SimpleXMLElement $datesAndDealsXML;

    public function setUp(): void
    {
        parent::setUp();
        $this->datesAndDealsString = file_get_contents('./tests/TourCMSResponses/datesAndDeals.xml');
        $this->datesAndDealsXML = simplexml_load_string($this->datesAndDealsString);
    }

    public function test_getCalendar_whenCallWithValidRequestParameters_thenWeGetValidCalendarArray()
    {
        // Given
        $availabilityCalendarServiceMock = $this->getMockBuilder(AvailabilityCalendarService::class)
            ->onlyMethods(['getAvailabilityRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTourDatesAndDeals'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTourDatesAndDeals')->willReturn($this->datesAndDealsXML);

        $availabilityCalendarServiceMock->tourCMSService = $tourCMSService;

        $requestParams = [
            "productId" => 184,
            "optionId" => 'SINGLE',
            "localDateStart" => '2024-11-21',
            "localDateEnd" => '2024-11-28'
        ];

        $availabilityRequestMock = new AvailabilityCalendarRequest('184', 'SINGLE', '2024-11-21', '2024-11-28', []);

        $availabilityCalendarServiceMock
            ->method('getAvailabilityRequest')
            ->with($requestParams)
            ->willReturn($availabilityRequestMock);

        // When
        $calendar = $availabilityCalendarServiceMock->getCalendar($requestParams);

        // Then
        $this->assertIsArray($calendar);
        $this->assertNotEmpty($calendar);
        $this->assertCount(self::DATES_AND_DEALS_XML_COUNT, $calendar);
    }

    public function test_validateRequestParams_whenCallWithMissingLocalDateEndParameter_thenShouldThrowAvailabilityRequestMissingParamException()
    {
        // Given
        $availabilityCalendarServiceMock = $this->getMockBuilder(AvailabilityCalendarService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        
        $requestParams = [
            "productId" => self::VALID_PRODUCT_ID,
            "optionId" => self::VALID_OPTION_ID,
            "localDateStart" => self::VALID_LOCAL_DATE_START
        ];

        // Then
        $this->expectException(AvailabilityRequestMissingParamException::class);

        $availabilityCalendarServiceMock->validateRequestParams($requestParams);
    }

    public function test_validateRequestParams_whenCallWithLocalDateEndEarlierThanLocalDateStart_thenShouldThrowAvailabilityRequestInvalidParamException()
    {
        // Given
        $availabilityCalendarServiceMock = $this->getMockBuilder(AvailabilityCalendarService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['validateProductId'])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->method('validateProductId')->willReturn(true);

        $optionServiceMock = $this->getMockBuilder(OptionService::class)
            ->onlyMethods(['validateOptionId'])
            ->disableOriginalConstructor()
            ->getMock();
        $optionServiceMock->method('validateOptionId')->willReturn(true);

        $availabilityCalendarServiceMock->productService = $productServiceMock;
        $availabilityCalendarServiceMock->optionService = $optionServiceMock;
        
        $requestParams = [
            "productId" => self::VALID_PRODUCT_ID,
            "optionId" => self::VALID_OPTION_ID,
            "localDateStart" => self::VALID_LOCAL_DATE_END,
            "localDateEnd" => self::VALID_LOCAL_DATE_START,
            "channel" => self::VALID_CHANNEL
        ];

        // Then
        $this->expectException(AvailabilityRequestInvalidParamException::class);

        $availabilityCalendarServiceMock->validateRequestParams($requestParams);
    }

    public function test_validateRequestParams_whenCallWithInvalidDate_thenShouldThrowAvailabilityRequestInvalidParamException()
    {
        // Given
        $availabilityCalendarServiceMock = $this->getMockBuilder(AvailabilityCalendarService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['validateProductId'])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->method('validateProductId')->willReturn(true);

        $optionServiceMock = $this->getMockBuilder(OptionService::class)
            ->onlyMethods(['validateOptionId'])
            ->disableOriginalConstructor()
            ->getMock();
        $optionServiceMock->method('validateOptionId')->willReturn(true);

        $availabilityCalendarServiceMock->productService = $productServiceMock;
        $availabilityCalendarServiceMock->optionService = $optionServiceMock;
        
        $requestParams = [
            "productId" => self::VALID_PRODUCT_ID,
            "optionId" => self::VALID_OPTION_ID,
            "localDateStart" => self::INVALID_LOCAL_DATE_START,
            "localDateEnd" => self::INVALID_LOCAL_DATE_END,
            "channel" => self::VALID_CHANNEL
        ];

        // Then
        $this->expectException(AvailabilityRequestInvalidParamException::class);

        $availabilityCalendarServiceMock->validateRequestParams($requestParams);
    }

    public function test_getAvailabilityRequest_whenCallWithValidProduct_thenWeGetValidAvailabilityRequest()
    {
        // Given
        $availabilityCalendarServiceMock = $this->getMockBuilder(AvailabilityCalendarService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        
        $productServiceMock = $this->getMockBuilder(ProductService::class)
        ->onlyMethods(['validateProductId'])
        ->disableOriginalConstructor()
        ->getMock();

        $availabilityCalendarServiceMock->productService = $productServiceMock;
        
        $requestParams = [
            "productId" => self::VALID_PRODUCT_ID,
            "optionId" => self::VALID_OPTION_ID,
            "localDateStart" => self::VALID_LOCAL_DATE_START,
            "localDateEnd" => self::VALID_LOCAL_DATE_END
        ];

        // When
        $availabilityRequest = $availabilityCalendarServiceMock->getAvailabilityRequest($requestParams);

        // Then
        $this->assertInstanceOf(AvailabilityCalendarRequest::class, $availabilityRequest);
        $this->assertEquals(explode('_',explode('|', $requestParams['productId'])[0])[2], $availabilityRequest->getTourId());
        $this->assertEquals($requestParams['optionId'], $availabilityRequest->getOptionId());
        $this->assertEquals($requestParams['localDateStart'], $availabilityRequest->getLocalDateStart());
        $this->assertEquals($requestParams['localDateEnd'], $availabilityRequest->getLocalDateEnd());
        $this->assertEquals([], $availabilityRequest->getUnits());
    }

    public function test_getAvailabilityRequest_whenCallWithEmptyParams_thenWeGetEmptyAvailabilityCalendarRequest()
    {
        // Given
        $availabilityCalendarServiceMock = $this->getMockBuilder(AvailabilityCalendarService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        
        $productServiceMock = $this->getMockBuilder(ProductService::class)
        ->onlyMethods(['validateProductId'])
        ->disableOriginalConstructor()
        ->getMock();

        $availabilityCalendarServiceMock->productService = $productServiceMock;
        
        $requestParams = [];

        // When
        $availabilityRequest = $availabilityCalendarServiceMock->getAvailabilityRequest($requestParams);

        // Then
        $this->assertInstanceOf(AvailabilityCalendarRequest::class, $availabilityRequest);
        $this->assertEquals("", $availabilityRequest->getTourId());
        $this->assertEquals("", $availabilityRequest->getOptionId());
        $this->assertEquals("", $availabilityRequest->getLocalDateStart());
        $this->assertEquals("", $availabilityRequest->getLocalDateEnd());
        $this->assertEquals([], $availabilityRequest->getUnits());
    }

}