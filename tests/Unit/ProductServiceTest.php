<?php

namespace Tests\Unit;

use App\Exceptions\InvalidProductContentException;
use App\Exceptions\InvalidProductIdException;
use App\Models\Product;
use App\Services\JSONLogService;
use App\Services\ProductService;
use App\Services\TourCMSService;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tests\UnitTestCase;

class ProductServiceTest extends UnitTestCase
{
    public string $showTourString;
    public string $listToursString;
    public string $showTourInvalidString;
    public SimpleXMLElement $showTourXML;
    public SimpleXMLElement $listToursXML;
    public SimpleXMLElement $showTourInvalidXML;
    public $loggerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->showTourString = file_get_contents('./tests/TourCMSResponses/showTour.xml');
        $this->listToursString = file_get_contents('./tests/TourCMSResponses/listTours.xml');
        $this->showTourInvalidString = file_get_contents('./tests/TourCMSResponses/showTourInvalid.xml');
        $this->showTourXML = simplexml_load_string($this->showTourString);
        $this->listToursXML = simplexml_load_string($this->listToursString);
        $this->showTourInvalidXML = simplexml_load_string($this->showTourInvalidString);
        $this->loggerMock = $this->getMockBuilder(JSONLogService::class)
            ->onlyMethods(['info', 'error'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function test_whenCallFind_thenWeGetValidStructure()
    {
        // Given
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->logger = $this->loggerMock;

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->method('findTourDataFromAPI')->willReturn($this->showTourXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";

        $product = $productServiceMock->find($productId);

        // Then
        $this->assertInstanceOf(Product::class, $product);
    }

    public function test_whenCallFindWithMultipleInvalidFields_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->method('findTourDataFromAPI')->willReturn($this->showTourInvalidXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $this->expectException(InvalidProductContentException::class);

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallFindWithoutTimeZone_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $apiResponseXML = $this->showTourXML;
        unset($apiResponseXML->tour->start_timezone);
        unset($apiResponseXML->tour->end_timezone);
        unset($apiResponseXML->tour->account_timezone);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: timeZone field is missing");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallFindAndTransformWithoutDeliveryFormats_thenShouldAssumeQRCODE()
    {
        // Given
        $apiResponseXML = $this->showTourXML;
        unset($apiResponseXML->tour->delivery_formats);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);

        // Then
        $this->assertEquals([$productServiceMock::DELIVERY_FORMAT_QRCODE], $product->getDeliveryFormats());
    }

    public function test_whenCallFindAndTransformWithInvalidDeliveryFormat_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $invalidDeliveryFormat = 'XML';
        $apiResponseXML = $this->showTourXML;
        $apiResponseDeliveryFormats = $apiResponseXML->tour->delivery_formats;
        $apiResponseDeliveryFormats->addChild('delivery_format', $invalidDeliveryFormat);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: invalid delivery format: {$invalidDeliveryFormat}");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallFindAndTransformWithoutDeliveryMethods_thenShouldAssumeVOUCHER()
    {
        // Given
        $apiResponseXML = $this->showTourXML;
        unset($apiResponseXML->tour->delivery_methods);
        
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);

        // Then
        $this->assertEquals([$productServiceMock::DELIVERY_METHOD_VOUCHER], $product->getDeliveryMethods());
    }

    public function test_whenCallFindWithInvalidDeliveryMethod_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $invalidDeliveryMethod = 'TICKETS';
        $apiResponseXML = $this->showTourXML;
        $apiResponseDeliveryMethods = $apiResponseXML->tour->delivery_methods;
        $apiResponseDeliveryMethods->addChild('delivery_method', $invalidDeliveryMethod);
        
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: invalid delivery method: {$invalidDeliveryMethod}");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallFindWithoutRedemptionMethod_thenShouldAssumeDIGITAL()
    {
        // Given
        $apiResponseXML = $this->showTourXML;
        unset($apiResponseXML->tour->redemption_method);
        
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);

        // Then
        $this->assertEquals($productServiceMock::REDEMPTION_METHOD_DIGITAL, $product->getRedemptionMethod());
    }

    public function test_whenCallFindWithInvalidRedemptionMethod_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $invalidRedemptionMethod = "ANALOGIC";
        $apiResponseXML = $this->showTourXML;
        $apiResponseXML->tour->redemption_method = $invalidRedemptionMethod;
        
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: invalid redemption method: {$invalidRedemptionMethod}");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallFindWithoutMapping_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $apiResponseXML = $this->showTourXML;
        unset($apiResponseXML->tour->tour_departure_structure->type);
        
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: the tour mapping is missing");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallFindWithUnsetMapping_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $apiResponseXML = $this->showTourXML;

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');
        
        $apiResponseXML->tour->tour_departure_structure->type = $productServiceMock::MAPPING_STRUCTURE_TYPE_NOTSET;

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: the tour departure structure is not set");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallValidateProductIdWithInvalidProductId_thenValidateProductIdShouldReturnFalse()
    {
        $authChannel = '143';
        $invalidProductIdList = [
            'TEa_1_2|143', 'TE_1c_2|143', 'TE_1_2cs|143', 'TE_1_2|14x3', 'TE_1_2|180', '1TE_1_2|143', '12_1_2|143', 'TE_B_2|143', 'TE_1_C|143', 'AB_1_2|143|2',
            'TE-1-2|143', 'TE_1_2_143', 'TEa_1c_2cs|14x3', 'TE_1_2/143', 'TE|1|2|143', 'TEE_1_2|143', 'TE_1_2|143|', 'a_c_cs|143'
        ];

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectException(InvalidProductIdException::class);
        // Then
        foreach ($invalidProductIdList as $invalidProductId) {
            $isProductValid = $productServiceMock->validateProductId($invalidProductId, $authChannel);
        }
    }

    public function test_whenCallGetProductList_thenWeGetValidStructure()
    {
        // Given
        $tourListData = [];
        foreach ($this->listToursXML->tour as $tour) {
            $tourListData[] = $tour;
        }

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['getTourListData', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->method('getTourListData')->willReturn($tourListData);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        
        // When
        $channelId = $this->listToursXML->tour->channel_id;

        $productList = $productServiceMock->getProductList($channelId);

        // Then
        $this->assertIsArray($productList);
        $this->assertNotEmpty($productList);
    }

    public function test_whenCallGetProductListWithInvalidTour_thenGetProductListShouldSkipTour()
    {
        // Given

        $tourListData = [];
        foreach ($this->listToursXML->tour as $tour) {
            $tourListData[] = $tour;
        }

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['getTourListData', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->method('getTourListData')->willReturn($tourListData);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $channelId = $this->listToursXML->tour->channel_id;

        $productList = $productServiceMock->getProductList($channelId);

        // Then
        $this->assertIsArray($productList);
        $this->assertNotEmpty($productList);
        $this->assertCount(1, $productList);
    }

    public function test_whenTourIsMappedBySingleDeparturePerDayButHaveMultipleStartTime_thenWeShouldThrowAnException(): void
    {
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
            
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SINGLE.xml'));
        $tourData = $showTourResponseXML->tour;

        $this->expectException(InvalidProductContentException::class);

        $productServiceMock->getActiveMappingsFromTour($tourData);
    }

    public function test_whenTourIsMappedBySingleDeparturePerDayAndHaveFixedStartTime_thenWeGetCorrectMapping(): void
    {
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
            
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SINGLE.xml'));
        $tourData = $showTourResponseXML->tour;
        $tourData->start_time = '13:00';

        $expectedProductOptions = [
            '' => ['13:00']
        ];

        $productOptions = $productServiceMock->getActiveMappingsFromTour($tourData);
        $this->assertEquals($expectedProductOptions, $productOptions);
    }

    public function test_whenTourIsMappedByStartTime_thenWeGetCorrectMappings(): void
    {
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
            
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/START_TIME.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            '' => ['13:00', '15:00', '17:00'],
        ];

        $productOptions = $productServiceMock->getActiveMappingsFromTour($tourData);
        $this->assertEquals($expectedProductOptions, $productOptions);
    }

    public function test_whenTourIsMappedBySupplierNote_thenWeGetCorrectMappings(): void
    {
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
            
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SUPPLIER_NOTE.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            '' => ['00:00'],
            'TEST_NOTE_1' => ['00:00'],
            'TEST_NOTE_2' => ['00:00'],
        ];

        $productOptions = $productServiceMock->getActiveMappingsFromTour($tourData);
        $this->assertEquals($expectedProductOptions, $productOptions);
    }

    public function test_whenTourIsMappedByDepartureCode_thenWeGetCorrectMappings(): void
    {
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
            
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/DEPARTURE_CODE.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            'ABC' => ['00:00'],
            '123' => ['00:00'],
            'xyz' => ['00:00'],
        ];

        $productOptions = $productServiceMock->getActiveMappingsFromTour($tourData);
        $this->assertEquals($expectedProductOptions, $productOptions);
    }

    public function test_whenTourIsMappedBySupplierNotePlusStartTime_thenWeGetOnlyNonPartialAndCorrectMappings(): void
    {
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
            
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SUPPLIER_NOTE_PLUS_START_TIME.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            'SUP_NOTE_TEST' => ['17:00', '13:00'],
        ];

        $productOptions = $productServiceMock->getActiveMappingsFromTour($tourData);
        error_log(print_r($productOptions, 1));
        $this->assertEquals($expectedProductOptions, $productOptions);
    }

    public function test_getOptionUnits_whenTourPermitOnlyChildIsFalseAndTourHasChildRate_thenChildRateUnitRestrictionAccompaniedByContainsAdultUnitId(): void
    {
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
            
        $showTourResponseXML = $this->showTourXML;
        $tourData = $showTourResponseXML->tour;
        $tourData->tour_permit_child_only = 0;

        $expectedAccompaniedBy = ['TE_1_184|r1'];

        $optionUnits = $productServiceMock->getOptionUnits($tourData);
        $this->assertNotEmpty($optionUnits);
        $this->assertEquals($expectedAccompaniedBy, $optionUnits[1]->getRestrictions()->getAccompaniedBy());
    }

    public function test_getOptionUnits_whenTourPermitOnlyChildIsTrueAndTourHasChildRate_thenChildRateUnitRestrictionAccompaniedByIsEmpty(): void
    {
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
            
        $showTourResponseXML = $this->showTourXML;
        $tourData = $showTourResponseXML->tour;
        $tourData->tour_permit_child_only = 1;

        $optionUnits = $productServiceMock->getOptionUnits($tourData);
        $this->assertEmpty($optionUnits[1]->getRestrictions()->getAccompaniedBy());
    }



    protected function mockLogger(): JSONLogService|MockObject
    {
        $logger = $this->getMockBuilder(JsonLogService::class)
        ->onlyMethods(['info', 'error'])
        ->disableOriginalConstructor()
        ->getMock();
        return $logger;
    }

    protected function mockTourCMSService(): TourCMSService|MockObject
    {
        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        return $tourCMSService;
    }
}