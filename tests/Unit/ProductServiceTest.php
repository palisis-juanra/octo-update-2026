<?php

namespace Tests\Unit;

use App\Exceptions\InvalidProductContentException;
use App\Http\Middleware\OctoAuthentication;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\TourCMSService;
use Tests\TestCase;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Request;

class ProductServiceTest extends TestCase
{
    public string $showTourString;
    public string $listToursString;
    public string $showTourInvalidString;
    public SimpleXMLElement $showTourXML;
    public SimpleXMLElement $listToursXML;
    public SimpleXMLElement $showTourInvalidXML;

    public function setUp(): void
    {
        parent::setUp();
        $this->showTourString = file_get_contents('./tests/TourCMSResponses/showTour.xml');
        $this->listToursString = file_get_contents('./tests/TourCMSResponses/listTours.xml');
        $this->showTourInvalidString = file_get_contents('./tests/TourCMSResponses/showTourInvalid.xml');
        $this->showTourXML = simplexml_load_string($this->showTourString);
        $this->listToursXML = simplexml_load_string($this->listToursString);
        $this->showTourInvalidXML = simplexml_load_string($this->showTourInvalidString);
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
            ->onlyMethods(['showTour'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($this->showTourXML);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

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
            ->onlyMethods(['showTour'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($this->showTourInvalidXML);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

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
            ->onlyMethods(['showTour'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: timeZone field is missing");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallFindAndTransformWithoutDeliveryFormats_thenShouldThrowInvalidProductContentException()
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
            ->onlyMethods(['showTour'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: delivery formats field is missing");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
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
            ->onlyMethods(['showTour'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: invalid delivery format: {$invalidDeliveryFormat}");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallFindAndTransformWithoutDeliveryMethods_thenShouldThrowInvalidProductContentException()
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
            ->onlyMethods(['showTour'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: delivery methods field is missing");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
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
            ->onlyMethods(['showTour'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: invalid delivery method: {$invalidDeliveryMethod}");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
    }

    public function test_whenCallFindWithoutRedemptionMethod_thenShouldThrowInvalidProductContentException()
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
            ->onlyMethods(['showTour'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

        // When
        $this->expectException(InvalidProductContentException::class);
        $this->expectExceptionMessage("The content of the product is invalid: redemption method field is missing");

        $productId = "{$this->showTourXML->tour->distribution_identifier}|{$this->showTourXML->tour->channel_id}";
        $product = $productServiceMock->find($productId);
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
            ->onlyMethods(['showTour'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

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
            ->onlyMethods(['showTour'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

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
        ->onlyMethods(['showTour'])
        ->disableOriginalConstructor()
        ->getMock();
        $tourCMSService->method('showTour')->willReturn($apiResponseXML);
        
        $productServiceMock = $this->getMockBuilder(ProductService::class)
        ->onlyMethods([])
        ->setConstructorArgs([$request, $tourCMSService])
        ->getMock();
        
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
            ->onlyMethods(['showTour'])
            ->disableOriginalConstructor()
            ->getMock();

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

        // When
        $splitPath = explode('/', $request->getPathInfo());
        $productId = end($splitPath);
        $authChannel = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);
        $isProductValid = $productServiceMock->validateProductId($productId, $authChannel);

        // Then
        $this->assertIsBool($isProductValid);
        $this->assertEquals(false, $isProductValid);
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
            ->onlyMethods(['listTours'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('listTours')->willReturn($this->listToursXML);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

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
            ->onlyMethods(['listTours'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('listTours')->willReturn($this->listToursXML);

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$request, $tourCMSService])
            ->getMock();

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
}