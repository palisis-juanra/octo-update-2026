<?php

namespace Tests\Unit;

use App\Exceptions\APIThrottleError;
use App\Exceptions\InvalidProductContentException;
use App\Exceptions\InvalidProductIdException;
use App\Models\Product;
use App\Models\ProductMapping;
use App\Services\JSONLogService;
use App\Services\LocaleService;
use App\Services\ProductMappingFactory;
use App\Services\ProductService;
use App\Services\TourCMSService;
use App\Services\TourPromotionService;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tests\UnitTestCase;
use TourCMS\Utils\TourCMS;

class ProductServiceTest extends UnitTestCase
{
    public string $showTourString;

    public string $listToursString;

    public string $showTourInvalidString;

    public SimpleXMLElement $showChannelXML;

    public SimpleXMLElement $showTourXML;

    public SimpleXMLElement $listToursXML;

    public SimpleXMLElement $showTourInvalidXML;

    public $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->showChannelXML = simplexml_load_file('./tests/TourCMSResponses/showChannel.xml');
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

    public function test_when_call_find_then_we_get_valid_structure()
    {
        // Given
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->logger = $this->loggerMock;

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
        $productServiceMock->method('findTourDataFromAPI')->willReturn($this->showTourXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";

        $product = $productServiceMock->find($productId);

        // Then
        $this->assertInstanceOf(Product::class, $product);
    }

    public function test_when_call_find_with_multiple_invalid_fields_then_should_throw_invalid_product_content_exception()
    {
        // Given
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
        $productServiceMock->method('findTourDataFromAPI')->willReturn($this->showTourInvalidXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $this->expectException(InvalidProductContentException::class);

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_find_without_time_zone_then_should_throw_invalid_product_content_exception()
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
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage('The content of the product is invalid: timeZone field is missing');

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_find_and_transform_without_delivery_formats_then_should_assume_qrcode()
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
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);

        // Then
        $this->assertEquals([$productServiceMock::DELIVERY_FORMAT_QRCODE], $product->getDeliveryFormats());
    }

    public function test_when_call_find_and_transform_with_invalid_delivery_format_then_should_throw_invalid_product_content_exception()
    {
        // Given
        $invalidDeliveryFormat = 'XML';
        $apiResponseXML = $this->showTourXML;
        unset($apiResponseXML->tour->delivery_formats);
        $apiResponseXML->tour->delivery_formats->delivery_format = $invalidDeliveryFormat;

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: invalid delivery format: {$invalidDeliveryFormat}");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_find_and_transform_without_delivery_methods_then_should_assume_voucher()
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
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);

        // Then
        $this->assertEquals([$productServiceMock::DELIVERY_METHOD_VOUCHER], $product->getDeliveryMethods());
    }

    public function test_when_call_find_with_invalid_delivery_method_then_should_throw_invalid_product_content_exception()
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
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: invalid delivery method: {$invalidDeliveryMethod}");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_find_without_redemption_method_then_should_assume_digital()
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
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);

        // Then
        $this->assertEquals($productServiceMock::REDEMPTION_METHOD_DIGITAL, $product->getRedemptionMethod());
    }

    public function test_when_call_find_with_invalid_redemption_method_then_should_throw_invalid_product_content_exception()
    {
        // Given
        $invalidRedemptionMethod = 'ANALOGIC';
        $apiResponseXML = $this->showTourXML;
        $apiResponseXML->tour->redemption_method = $invalidRedemptionMethod;

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: invalid redemption method: {$invalidRedemptionMethod}");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_find_without_mapping_then_should_throw_invalid_product_content_exception()
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
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage('The content of the product is invalid: the tour mapping is missing');

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_find_with_unset_mapping_then_should_throw_invalid_product_content_exception()
    {
        // Given
        $apiResponseXML = $this->showTourXML;

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods(['findTourDataFromAPI', 'getProductLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
        $productServiceMock->method('findTourDataFromAPI')->willReturn($apiResponseXML->tour);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        $apiResponseXML->tour->tour_departure_structure->type = $productServiceMock::MAPPING_STRUCTURE_TYPE_NOTSET;

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage('The content of the product is invalid: the tour departure structure is not set');

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_validate_product_id_with_invalid_product_id_then_validate_product_id_should_return_false()
    {
        $authChannel = '143';
        $invalidProductIdList = [
            'TEa_1_2|143', 'TE_1c_2|143', 'TE_1_2cs|143', 'TE_1_2|14x3', 'TE_1_2|180', '1TE_1_2|143', '12_1_2|143', 'TE_B_2|143', 'TE_1_C|143', 'AB_1_2|143|2',
            'TE-1-2|143', 'TE_1_2_143', 'TEa_1c_2cs|14x3', 'TE_1_2/143', 'TE|1|2|143', 'TEE_1_2|143', 'TE_1_2|143|', 'a_c_cs|143',
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

    public function test_when_call_get_product_list_then_we_get_valid_structure()
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
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
        $productServiceMock->method('getTourListData')->willReturn($tourListData);
        $productServiceMock->method('getProductLocale')->willReturn('es-ES');

        // When
        $channelId = $this->listToursXML->tour->channel_id;

        $productList = $productServiceMock->getProductList($channelId);

        // Then
        $this->assertIsArray($productList);
        $this->assertNotEmpty($productList);
    }

    public function test_when_call_get_product_list_with_invalid_tour_then_get_product_list_should_skip_tour()
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
        $productServiceMock->productMappingFactory = new ProductMappingFactory;
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

    public function test_get_option_units_when_tour_permit_only_child_is_false_and_tour_has_child_rate_then_child_rate_unit_restriction_accompanied_by_contains_adult_unit_id(): void
    {
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->productMappingFactory = new ProductMappingFactory;

        $showTourResponseXML = $this->showTourXML;
        $tourData = $showTourResponseXML->tour;
        $tourData->tour_permit_child_only = 0;

        $expectedAccompaniedBy = ["{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}|r1"];

        $optionUnits = $productServiceMock->getOptionUnits($tourData);
        $this->assertNotEmpty($optionUnits);
        $this->assertEquals($expectedAccompaniedBy, $optionUnits[1]->getRestrictions()->getAccompaniedBy());
    }

    public function test_get_option_units_when_tour_permit_only_child_is_true_and_tour_has_child_rate_then_child_rate_unit_restriction_accompanied_by_is_empty(): void
    {
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $productServiceMock->tourCMSService = $this->mockTourCMSService();
        $productServiceMock->logger = $this->mockLogger();
        $productServiceMock->productMappingFactory = new ProductMappingFactory;

        $showTourResponseXML = $this->showTourXML;
        $tourData = $showTourResponseXML->tour;
        $tourData->tour_permit_child_only = 1;

        $optionUnits = $productServiceMock->getOptionUnits($tourData);
        $this->assertEmpty($optionUnits[1]->getRestrictions()->getAccompaniedBy());
    }

    public function test_when_tour_has_start_timezone_then_product_has_tour_start_timezone_as_timezone(): void
    {
        $tourPromotionServiceMock = $this->getMockBuilder(TourPromotionService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productService = new ProductService($this->mockTourCMSService(), $this->mockLogger(), new LocaleService, new ProductMappingFactory, $tourPromotionServiceMock);

        $timezone = $productService->getProductTimeZone($this->showTourXML->tour);
        $this->assertSame((string) $this->showTourXML->tour->start_timezone, $timezone);
    }

    public function test_when_tour_has_not_start_timezone_then_product_has_tour_end_timezone_as_timezone(): void
    {
        $tourPromotionServiceMock = $this->getMockBuilder(TourPromotionService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productService = new ProductService($this->mockTourCMSService(), $this->mockLogger(), new LocaleService, new ProductMappingFactory, $tourPromotionServiceMock);

        unset($this->showTourXML->tour->start_timezone);
        $timezone = $productService->getProductTimeZone($this->showTourXML->tour);
        $this->assertSame((string) $this->showTourXML->tour->end_timezone, $timezone);

        $this->showTourXML->tour->addChild('start_timezone', '');
        $timezone = $productService->getProductTimeZone($this->showTourXML->tour);
        $this->assertSame((string) $this->showTourXML->tour->end_timezone, $timezone);

        $this->showTourXML->tour->start_timezone = 'NOTSET';
        $timezone = $productService->getProductTimeZone($this->showTourXML->tour);
        $this->assertSame((string) $this->showTourXML->tour->end_timezone, $timezone);
    }

    public function test_when_tour_has_neither_start_nor_end_timezone_then_product_has_account_timezone_as_timezone(): void
    {
        $tourPromotionServiceMock = $this->getMockBuilder(TourPromotionService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productService = new ProductService($this->mockTourCMSService(), $this->mockLogger(), new LocaleService, new ProductMappingFactory, $tourPromotionServiceMock);

        unset($this->showTourXML->tour->start_timezone);
        unset($this->showTourXML->tour->end_timezone);

        $timezone = $productService->getProductTimeZone($this->showTourXML->tour);
        $this->assertSame((string) $this->showTourXML->tour->account_timezone, $timezone);

        $this->showTourXML->tour->addChild('end_timezone', '');
        $timezone = $productService->getProductTimeZone($this->showTourXML->tour);
        $this->assertSame((string) $this->showTourXML->tour->account_timezone, $timezone);

        $this->showTourXML->tour->end_timezone = 'NOTSET';
        $timezone = $productService->getProductTimeZone($this->showTourXML->tour);
        $this->assertSame((string) $this->showTourXML->tour->account_timezone, $timezone);
    }

    public function test_when_product_id_has_invalid_distribution_identifier_then_we_throw_exception(): void
    {
        $this->showTourXML->tour->distribution_identifier = 'TE_1_2';

        $tourcmsService = $this->mockTourCMSService(['showTour']);
        $tourcmsService->method('showTour')->willReturn($this->showTourXML);

        $tourPromotionServiceMock = $this->getMockBuilder(TourPromotionService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productService = new ProductService($tourcmsService, $this->mockLogger(), new LocaleService, new ProductMappingFactory, $tourPromotionServiceMock);

        $this->expectException(InvalidProductIdException::class);
        $productService->find('AA_1_2|143');

        $this->expectException(InvalidProductIdException::class);
        $productService->find('AA_12_2|143');

        $this->expectException(InvalidProductIdException::class);
        $productService->find('AA_1_28|143');

        $this->expectException(InvalidProductIdException::class);
        $productService->find('AA_1_2|27');
    }

    public function test_get_product_options_when_tour_is_non_refundable_then_option_has_correct_cancellation_policy(): void
    {
        $this->showTourXML->tour->non_refundable = 1;

        $tourcmsService = $this->mockTourCMSService(['showTour']);
        $tourcmsService->method('showTour')->willReturn($this->showTourXML);

        $tourPromotionServiceMock = $this->getMockBuilder(TourPromotionService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productService = new ProductService($tourcmsService, $this->mockLogger(), new LocaleService, new ProductMappingFactory, $tourPromotionServiceMock);

        $options = $productService->getProductOptions($this->showTourXML->tour);

        foreach ($options as $option) {
            $this->assertEquals(ProductService::CANCELLATION_CUTOFF_NON_REFUNDABLE, $option->getCancellationCutoff());
            $this->assertEquals(ProductService::CANCELLATION_CUTOFF_AMOUNT_NON_REFUNDABLE, $option->getCancellationCutoffAmount());
            $this->assertEquals(ProductService::CANCELLATION_CUTOFF_UNIT_DAY, $option->getCancellationCutoffUnit());
        }
    }

    public function test_when_show_tour_response_is_rate_limited_then_we_throw_an_exception(): void
    {
        $this->showTourXML->tour->distribution_identifier = 'TE_1_230';

        $throttledResponse = simplexml_load_file('./tests/TourCMSResponses/showTourThrottled.xml');
        $tourCMSMock = $this->getMockBuilder(TourCMS::class)
            ->onlyMethods(['show_tour'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSMock->method('show_tour')->willReturn($throttledResponse);

        $jsonLogService = $this->getMockBuilder(JSONLogService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['info', 'getLogId'])
            ->getMock();
        $jsonLogService->method('getLogId')->willReturn('xxx');
        $jsonLogService
            ->expects($this->any())
            ->method('info');

        $cache = $this->createMock(\Illuminate\Contracts\Cache\Repository::class);
        $cache->method('get')->willReturn(null);
        $cache->method('put')->willReturn($cache);

        $tourcmsService = new TourCMSService('12345', 'abcde', '142', $jsonLogService, $cache);
        $tourcmsService->setTourCMS($tourCMSMock);

        $tourPromotionServiceMock = $this->getMockBuilder(TourPromotionService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productService = new ProductService($tourcmsService, $this->mockLogger(), new LocaleService, new ProductMappingFactory, $tourPromotionServiceMock);

        $this->expectException(APIThrottleError::class);
        $productService->find('TE_1_230|142');
    }

    public function test_get_option_title_when_custom_label_present_then_title_is_custom_label(): void
    {
        $tourPromotionServiceMock = $this->getMockBuilder(TourPromotionService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productService = new ProductService($this->mockTourCMSService(), $this->mockLogger(), new LocaleService, new ProductMappingFactory, $tourPromotionServiceMock);

        // A partial mapping with a custom label: the title must be the custom label, not the
        // first-departure note, so a single start time is not exposed to OCTO.
        $productMapping = new ProductMapping(
            ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME,
            '{"en":"Semi-Private - English - 09:30 AM"}',
            'ESP_[*]',
            ['08:00', '09:30'],
            'English - Group'
        );

        $this->assertSame('English - Group', $productService->getOptionTitle('Tour Name', $productMapping));
    }

    public function test_get_option_title_when_no_custom_label_then_title_falls_back_to_first_departure_label(): void
    {
        $tourPromotionServiceMock = $this->getMockBuilder(TourPromotionService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productService = new ProductService($this->mockTourCMSService(), $this->mockLogger(), new LocaleService, new ProductMappingFactory, $tourPromotionServiceMock);

        // No custom label: behaviour is unchanged, the per-language label (first-departure note) is used.
        $productMapping = new ProductMapping(
            ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME,
            '{"en":"Semi-Private - English - 09:30 AM"}',
            'ESP_[*]',
            ['08:00', '09:30']
        );

        $this->assertSame('Semi-Private - English - 09:30 AM', $productService->getOptionTitle('Tour Name', $productMapping));
    }

    // PROTECTED METHODS

    protected function mockLogger(): JSONLogService|MockObject
    {
        $logger = $this->getMockBuilder(JsonLogService::class)
            ->onlyMethods(['info', 'error'])
            ->disableOriginalConstructor()
            ->getMock();

        return $logger;
    }

    protected function mockTourCMSService(array $methodsToMock = []): TourCMSService|MockObject
    {
        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods([...$methodsToMock, 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        return $tourCMSService;
    }
}
