<?php

namespace Tests\Feature;

use App\Exceptions\InvalidProductContentException;
use App\Exceptions\InvalidProductIdException;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Requests\OctoRequest;
use App\Http\Responses\OctoResponse;
use App\Models\Pricing;
use App\Models\Product;
use App\Services\JSONLogService;
use App\Services\LocaleService;
use App\Services\ProductMappingFactory;
use App\Services\ProductService;
use App\Services\TourCMSService;
use App\Services\XMLService;
use App\Transformers\BaseTransformer;
use App\Transformers\ProductTransformer;
use Illuminate\Support\Facades\App;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Request;
use Tests\FeatureTestCase;

class ProductTest extends FeatureTestCase
{
    public string $showTourString;

    public string $listToursString;

    public string $showTourInvalidString;

    public SimpleXMLElement $showChannelXML;

    public SimpleXMLElement $showTourXML;

    public SimpleXMLElement $listToursXML;

    public SimpleXMLElement $showTourInvalidXML;

    public SimpleXMLElement $showTourWithGeocodesXML;

    public SimpleXMLElement $showQuantityBasedPricingTourXML;

    public $loggerMock;

    public $tourCMSServiceMock;

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
        $this->showTourWithGeocodesXML = simplexml_load_file('./tests/TourCMSResponses/showTourWithGeocodesAndGooglePlaceId.xml');
        $this->showQuantityBasedPricingTourXML = simplexml_load_file('./tests/TourCMSResponses/showQuantityBasedPricingTour.xml');

        $this->loggerMock = $this->getMockBuilder(JSONLogService::class)
            ->onlyMethods(['info', 'error'])
            ->disableOriginalConstructor()
            ->getMock();
        App::instance(JSONLogService::class, $this->loggerMock);

        $this->tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function test_when_call_find_and_transform_then_we_get_valid_structure(): void
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($this->showTourXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);
        // When
        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";

        $product = $productServiceMock->find($productId);
        $productData = $productServiceMock->transform($product);

        // Then
        $this->assertInstanceOf(Product::class, $product);
        $this->assertIsArray($productData);
        $this->assertNotEmpty($productData);
    }

    public function test_when_call_find_and_transform_with_multiple_invalid_fields_then_should_throw_invalid_product_content_exception(): void
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($this->showTourInvalidXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        // When
        $this->expectException(InvalidProductContentException::class);

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_find_and_transform_without_time_zone_then_should_throw_invalid_product_content_exception(): void
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $apiResponseXML = $this->showTourXML;
        unset($apiResponseXML->tour->start_timezone);
        unset($apiResponseXML->tour->end_timezone);
        unset($apiResponseXML->tour->account_timezone);

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage('The content of the product is invalid: timeZone field is missing');

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_find_and_transform_without_delivery_formats_then_should_assume_qrcode(): void
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $apiResponseXML = $this->showTourXML;
        unset($apiResponseXML->tour->delivery_formats);

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        // When
        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);

        // Then
        $this->assertEquals([$productServiceMock::DELIVERY_FORMAT_QRCODE], $product->getDeliveryFormats());
    }

    public function test_when_call_find_and_transform_with_invalid_delivery_format_then_should_throw_invalid_product_content_exception(): void
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $invalidDeliveryFormat = (string) $this->showTourInvalidXML->tour->delivery_formats->delivery_format;
        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($this->showTourInvalidXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: invalid delivery format: {$invalidDeliveryFormat}");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_find_and_transform_without_delivery_methods_then_should_assume_voucher(): void
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $apiResponseXML = $this->showTourXML;
        unset($apiResponseXML->tour->delivery_methods);

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        // When
        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);

        // Then
        $this->assertEquals([$productServiceMock::DELIVERY_METHOD_VOUCHER], $product->getDeliveryMethods());
    }

    public function test_when_call_find_with_invalid_delivery_method_then_should_throw_invalid_product_content_exception(): void
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $invalidDeliveryMethod = 'TICKETS';
        $apiResponseXML = $this->showTourXML;
        $apiResponseDeliveryMethods = $apiResponseXML->tour->delivery_methods;
        $apiResponseDeliveryMethods->addChild('delivery_method', $invalidDeliveryMethod);

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: invalid delivery method: {$invalidDeliveryMethod}");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_find_without_redemption_method_then_should_assume_digital(): void
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $apiResponseXML = $this->showTourXML;
        unset($apiResponseXML->tour->redemption_method);

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        // When
        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);

        // Then
        $this->assertEquals($productServiceMock::REDEMPTION_METHOD_DIGITAL, $product->getRedemptionMethod());
    }

    public function test_when_call_find_with_invalid_redemption_method_then_should_throw_invalid_product_content_exception(): void
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $invalidRedemptionMethod = 'ANALOGIC';
        $apiResponseXML = $this->showTourXML;
        $apiResponseXML->tour->redemption_method = $invalidRedemptionMethod;

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: invalid redemption method: {$invalidRedemptionMethod}");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_find_without_mapping_then_should_throw_invalid_product_content_exception(): void
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $apiResponseXML = $this->showTourXML;
        unset($apiResponseXML->tour->tour_departure_structure->type);

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage('The content of the product is invalid: the tour mapping is missing');

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_find_with_unset_mapping_then_should_throw_invalid_product_content_exception(): void
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $apiResponseXML = $this->showTourXML;

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        $apiResponseXML->tour->tour_departure_structure->type = $productServiceMock::MAPPING_STRUCTURE_TYPE_NOTSET;

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage('The content of the product is invalid: the tour departure structure is not set');

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_when_call_validate_product_id_with_invalid_product_id_then_validate_product_id_should_return_false(): void
    {
        // Given
        $request = Request::create('/products/TEa_1c_2cs|14x3', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);
        $this->expectException(InvalidProductIdException::class);

        // When
        $splitPath = explode('/', $request->getPathInfo());
        $productId = end($splitPath);
        $authChannel = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);

        // Then
        $productServiceMock->validateProductId($productId, $authChannel);
    }

    public function test_when_call_get_product_list_and_transform_then_we_get_valid_structure(): void
    {
        // Given
        $request = Request::create('/products', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['listTours', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('listTours')->willReturn($this->listToursXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        // When
        $channelId = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);

        $productList = $productServiceMock->getProductList($channelId);
        $productListData = $productServiceMock->transformList($productList);

        // Then
        $this->assertIsArray($productList);
        $this->assertNotEmpty($productList);
        $this->assertIsArray($productListData);
        $this->assertNotEmpty($productListData);
    }

    public function test_when_call_get_product_list_and_transform_with_invalid_tour_then_get_product_list_should_skip_tour(): void
    {
        // Given
        $request = Request::create('/products', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => '',
        ]);

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['listTours', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('listTours')->willReturn($this->listToursXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        // When
        $channelId = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);

        $productList = $productServiceMock->getProductList($channelId);
        $productListData = $productServiceMock->transformList($productList);

        // Then
        $this->assertIsArray($productList);
        $this->assertNotEmpty($productList);
        $this->assertCount(1, $productList);
        $this->assertIsArray($productListData);
        $this->assertNotEmpty($productListData);
        $this->assertCount(1, $productListData);
    }

    public function test_when_we_send_pricing_capability_then_we_received_product_with_pricing_info(): void
    {
        $this->showTourXML->tour->distribution_identifier = 'TE_1_231';

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($this->showTourXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);
        App::instance(TourCMSService::class, $tourCMSService);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);
        App::instance(ProductService::class, $productServiceMock);

        $response = $this->get(
            '/products/TE_1_231|142',
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'defaultCurrency' => (string) $this->showTourXML->tour->sale_currency,
                'availableCurrencies' => [(string) $this->showTourXML->tour->sale_currency],
                'pricingPer' => Product::PRICING_PER_UNIT,
            ]);

    }

    public function test_when_we_send_pricing_capability_then_we_received_products_with_pricing_info(): void
    {
        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['listTours', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('listTours')->willReturn($this->listToursXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);
        App::instance(TourCMSService::class, $tourCMSService);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);
        App::instance(ProductService::class, $productServiceMock);

        $response = $this->get(
            '/products',
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING,
            ]
        );

        $response->assertOk();

        foreach (XMLService::getArrayFromXmlNode($this->listToursXML, 'tour') as $tour) {
            $response->assertJsonFragment([
                'defaultCurrency' => (string) $this->showChannelXML->channel->sale_currency,
                'availableCurrencies' => [(string) $this->showChannelXML->channel->sale_currency],
                'pricingPer' => Product::PRICING_PER_UNIT,
            ]);
        }

    }

    public function test_when_we_send_pricing_capability_and_tour_has_group_pricing_then_we_received_invalid_id(): void
    {
        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->showTourXML->tour->distribution_identifier = 'TE_1_231';
        $this->showTourXML->tour->quantity_rule = 1;

        $tourCMSService->method('showTour')->willReturn($this->showTourXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);
        App::instance(TourCMSService::class, $tourCMSService);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);
        App::instance(ProductService::class, $productServiceMock);

        $productId = 'TE_1_231|142';

        $response = $this->get(
            "/products/{$productId}",
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING,
            ]
        );

        $response
            ->assertStatus(400)
            ->assertJsonFragment([
                OctoResponse::FIELD_ERROR => OctoResponse::ERROR_CODE_INVALID_PRODUCT_ID,
            ]
            )->assertJsonFragment([
                OctoResponse::FIELD_PRODUCT_ID => $productId,
            ]
            );
    }

    public function test_when_pricing_capability_is_set_and_product_have_quantity_based_pricing_then_we_only_have_one_unit(): void
    {
        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->showQuantityBasedPricingTourXML->tour->distribution_identifier = 'TE_1_232';

        $tourCMSService->method('showTour')->willReturn($this->showQuantityBasedPricingTourXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);
        App::instance(TourCMSService::class, $tourCMSService);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);
        App::instance(ProductService::class, $productServiceMock);

        $productId = 'TE_1_232|142';

        $response = $this->get(
            "/products/{$productId}",
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING,
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'options');
        $response->assertJsonPath('options.0.units.0.id', 'TE_1_232|142|r1');
        $response->assertJsonMissingPath('options.0.units.1');
    }

    public function test_when_pricing_capability_is_set_and_product_have_quantity_based_pricing_then_we_get_price_from_rate_one(): void
    {
        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->showQuantityBasedPricingTourXML->tour->distribution_identifier = 'TE_1_232';

        $tourCMSService->method('showTour')->willReturn($this->showQuantityBasedPricingTourXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);
        App::instance(TourCMSService::class, $tourCMSService);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);
        App::instance(ProductService::class, $productServiceMock);

        $productId = 'TE_1_232|142';

        $response = $this->get(
            "/products/{$productId}",
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING,
            ]
        );

        $expectedCurrency = (string) $this->showQuantityBasedPricingTourXML->tour->sale_currency;

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'defaultCurrency' => $expectedCurrency,
            'availableCurrencies' => [$expectedCurrency],
            'pricingPer' => Product::PRICING_PER_UNIT,
        ]);
        $response->assertJsonFragment([
            'pricingFrom' => [
                [
                    'currency' => $expectedCurrency,
                    'currencyPrecision' => Pricing::CURRENCY_PRECISION,
                    'includedTaxes' => [],
                    'retail' => $this->showQuantityBasedPricingTourXML->tour->new_booking->people_selection->rate->from_price * 100,
                    'original' => $this->showQuantityBasedPricingTourXML->tour->new_booking->people_selection->rate->from_price * 100,
                    'net' => null,
                ],
            ],
        ]);
    }

    protected function getProductServiceMock(array $properties = [])
    {
        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock->logger = $properties['logger'] ?? $this->loggerMock;
        $productServiceMock->tourCMSService = $properties['tourCMSService'] ?? $this->tourCMSServiceMock;
        $productServiceMock->localeService = new LocaleService;
        $productServiceMock->productTransformer = new ProductTransformer(BaseTransformer::FULL_TRANSFORM);
        $productServiceMock->productMappingFactory = new ProductMappingFactory;

        return $productServiceMock;
    }
}
