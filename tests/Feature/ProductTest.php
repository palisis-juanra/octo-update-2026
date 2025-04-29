<?php

namespace Tests\Feature;

use App\Exceptions\InvalidProductContentException;
use App\Exceptions\InvalidProductIdException;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Requests\OctoRequest;
use App\Models\Product;
use App\Services\JSONLogService;
use App\Services\LocaleService;
use App\Services\ProductService;
use App\Services\TourCMSService;
use App\Services\XMLService;
use App\Transformers\BaseTransformer;
use App\Transformers\ProductTransformer;
use Illuminate\Support\Facades\App;
use Tests\FeatureTestCase;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Request;

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
    public $loggerMock;
    public $tourCMSServiceMock;

    public function setUp(): void
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

        $this->loggerMock = $this->getMockBuilder(JSONLogService::class)
            ->onlyMethods(['info', 'error'])
            ->disableOriginalConstructor()
            ->getMock();
        App::instance(JSONLogService::class, $this->loggerMock);

        
        $this->tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
        ->disableOriginalConstructor()
        ->getMock();
    }

    public function test_whenCallFindAndTransform_thenWeGetValidStructure()
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
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

    public function test_whenCallFindAndTransformWithMultipleInvalidFields_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
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

    public function test_whenCallFindAndTransformWithoutTimeZone_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
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
        $this->expectExceptionMessage("The content of the product is invalid: timeZone field is missing");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallFindAndTransformWithoutDeliveryFormats_thenShouldAssumeQRCODE()
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
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

    public function test_whenCallFindAndTransformWithInvalidDeliveryFormat_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
        ]);

        $invalidDeliveryFormat = 'XML';
        $apiResponseXML = $this->showTourXML;
        $apiResponseDeliveryFormats = $apiResponseXML->tour->delivery_formats;
        $apiResponseDeliveryFormats->addChild('delivery_format', $invalidDeliveryFormat);

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: invalid delivery format: {$invalidDeliveryFormat}");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallFindAndTransformWithoutDeliveryMethods_thenShouldAssumeVOUCHER()
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
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

    public function test_whenCallFindWithInvalidDeliveryMethod_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
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

    public function test_whenCallFindWithoutRedemptionMethod_thenShouldAssumeDIGITAL()
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
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

    public function test_whenCallFindWithInvalidRedemptionMethod_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
        ]);

        $invalidRedemptionMethod = "ANALOGIC";
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

    public function test_whenCallFindWithoutMapping_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
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
        $this->expectExceptionMessage("The content of the product is invalid: the tour mapping is missing");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallFindWithUnsetMapping_thenShouldThrowInvalidProductContentException()
    {
        // Given
        $request = Request::create('/products/TE_1_2|143', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
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
        $this->expectExceptionMessage("The content of the product is invalid: the tour departure structure is not set");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallValidateProductIdWithInvalidProductId_thenValidateProductIdShouldReturnFalse()
    {
        // Given
        $request = Request::create('/products/TEa_1c_2cs|14x3', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
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
    
    public function test_whenCallGetProductListAndTransform_thenWeGetValidStructure()
    {
        // Given
        $request = Request::create('/products', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
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

    public function test_whenCallGetProductListAndTransformWithInvalidTour_thenGetProductListShouldSkipTour()
    {
        // Given
        $request = Request::create('/products', 'GET', [
            'maid' => '12345',
            'channel' => '143',
            'APIKey' => 'ccadca970eea',
            'X-Correlation-Id' => '',
            'X-Request-Id' => ''
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

    public function test_whenWeSendPricingCapability_thenWeReceivedProductWithPricingInfo(): void
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
            "/products/TE_1_231|142",
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                "defaultCurrency" => (string) $this->showTourXML->tour->sale_currency,
                "availableCurrencies" => [(string) $this->showTourXML->tour->sale_currency],
                "pricingPer" => Product::PRICING_PER_UNIT
            ]);

    }

    public function test_whenWeSendPricingCapability_thenWeReceivedProductsWithPricingInfo(): void
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
            "/products",
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $response->assertOk();

        foreach (XMLService::getArrayFromXmlNode($this->listToursXML, 'tour') as $tour) {
            $response->assertJsonFragment([
                "defaultCurrency" => (string) $this->showChannelXML->channel->sale_currency,
                "availableCurrencies" => [(string) $this->showChannelXML->channel->sale_currency],
                "pricingPer" => Product::PRICING_PER_UNIT
            ]);  
        }

    }

    public function test_whenWeSendPricingCapabilityAndTourHasGroupPricing_thenWeReceivedProductsWithPricingPerBooking(): void
    {
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
            "/products/TE_1_231|142",
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                "defaultCurrency" => (string) $this->showTourXML->tour->sale_currency,
                "availableCurrencies" => [(string) $this->showTourXML->tour->sale_currency],
                "pricingPer" => Product::PRICING_PER_BOOKING
            ]);

    }

    public function test_whenProductHaveGooglePlaceId_thenWeGetItOnResponse(): void
    {
        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTour', 'showChannel'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->showTourWithGeocodesXML->tour->distribution_identifier = 'TE_1_231';

        $tourCMSService->method('showTour')->willReturn($this->showTourWithGeocodesXML);
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);
        App::instance(TourCMSService::class, $tourCMSService);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $tourCMSService]);
        App::instance(ProductService::class, $productServiceMock);

        $productId = "TE_1_231|142";

        $response = $this->get(
            "/products/{$productId}",
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_CONTENT
            ]
        );

        $response->assertStatus(200);

        // Start point
        $response->assertJsonFragment([
            "identifiers" => [
                [
                    ProductService::FIELD_IDENTIFIER_VALUE => (string) $this->showTourWithGeocodesXML->tour->geocode_start_point->google_place_id,
                    ProductService::FIELD_IDENTIFIER_TYPE => ProductService::IDENTIFIER_TYPE_GOOGLE_PLACE_ID
                ]
            ]
        ]);

        // Midpoints
        foreach ($this->showTourWithGeocodesXML->tour->geocode_midpoints->midpoint as $midpoint) {
            $response
                ->assertJsonFragment([
                    "identifiers" => [
                        [
                            ProductService::FIELD_IDENTIFIER_VALUE => (string) $midpoint->google_place_id,
                            ProductService::FIELD_IDENTIFIER_TYPE => ProductService::IDENTIFIER_TYPE_GOOGLE_PLACE_ID
                        ]
                    ]
                ]);
        }
        // End point
        $response->assertJsonFragment([
            "identifiers" => [
                [
                    ProductService::FIELD_IDENTIFIER_VALUE => (string) $this->showTourWithGeocodesXML->tour->geocode_end_point->google_place_id,
                    ProductService::FIELD_IDENTIFIER_TYPE => ProductService::IDENTIFIER_TYPE_GOOGLE_PLACE_ID
                ]
            ]
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

        return $productServiceMock;
    }
}