<?php

namespace Tests\Unit;

use App\Exceptions\AvailabilityRequestInvalidParamException;
use App\Exceptions\AvailabilityRequestMissingParamException;
use App\Models\Availability\AvailabilityCalendarRequest;
use App\Services\AvailabilityCalendarService;
use App\Services\ProductService;
use App\Services\TourCMSService;
use Tests\TestCase;
use SimpleXMLElement;
use App\Services\OptionService;

class AvailabilityCalendarServiceTest extends TestCase
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

    public function test_whenCallGetCalendar_thenWeGetValidCalendarArray()
    {
        // Given
        $availabilityCalendarServiceMock = $this->getMockBuilder(AvailabilityCalendarService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        
        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTourDatesAndDeals'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTourDatesAndDeals')->willReturn($this->datesAndDealsXML);

        $availabilityCalendarServiceMock->tourCMSService = $tourCMSService;

        $availabilityRequestMock = new AvailabilityCalendarRequest('184', 'SINGLE', '2024-11-21', '2024-11-28', []);

        // When
        $calendar = $availabilityCalendarServiceMock->getCalendar($availabilityRequestMock);

        // Then
        $this->assertIsArray($calendar);
        $this->assertNotEmpty($calendar);
        $this->assertCount(self::DATES_AND_DEALS_XML_COUNT, $calendar);
    }

    public function test_whenCallValidateRequestParamsWithMissingParams_thenShouldThrowAvailabilityRequestMissingParamException()
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

    public function test_whenCallValidateRequestParamsWithInvalidDate_thenShouldThrowAvailabilityRequestInvalidParamException()
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

    public function test_whenCallGetAvailabilityRequest_thenWeGetValidStructure()
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

    public function test_whenCallGetAvailabilityRequestWithEmptyParams_thenWeGetEmptyPropertiesStructure()
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