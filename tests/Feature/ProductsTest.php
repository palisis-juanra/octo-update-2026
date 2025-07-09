<?php

namespace Tests\Feature;

use App\Http\Requests\OctoRequest;
use App\Http\Responses\OctoResponse;
use App\Services\JSONLogService;
use App\Services\LocaleService;
use App\Services\ProductMappingFactory;
use App\Services\ProductService;
use App\Services\TourCMSService;
use App\Transformers\BaseTransformer;
use App\Transformers\ProductTransformer;
use Illuminate\Support\Facades\App;
use SimpleXMLElement;
use Tests\FeatureTestCase;

class ProductsTest extends FeatureTestCase
{
    protected const GROUP_PRICING_PRODUCT_ID = "TE_1_270|142";
    protected const HOTEL_PRODUCT_ID = "TE_1_270|64";
    protected const FREESALE_PRODUCT_ID = "TE_1_270|184";
    protected SimpleXMLElement $showChannelXML;
    protected SimpleXMLElement $listToursXML;
    protected $loggerMock;
    protected $tourCMSServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->showChannelXML = simplexml_load_file('./tests/TourCMSResponses/showChannel.xml');
        $this->listToursXML = simplexml_load_file('./tests/TourCMSResponses/listTours.xml');

        $this->loggerMock = $this->getMockBuilder(JSONLogService::class)
            ->onlyMethods(['info', 'error'])
            ->disableOriginalConstructor()
            ->getMock();
        App::instance(JSONLogService::class, $this->loggerMock);

        
        $this->tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
        ->disableOriginalConstructor()
        ->getMock();
    }

    public function test_ProductsWithGroupPricingShouldBeSkipped(): void
    {
    
        $this->tourCMSServiceMock->method('showChannel')->willReturn($this->showChannelXML);
        $this->tourCMSServiceMock->method('listTours')->willReturn($this->listToursXML);
        
        App::instance(TourCMSService::class, $this->tourCMSServiceMock);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $this->tourCMSServiceMock]);
        App::instance(ProductService::class, $productServiceMock);


        $response = $this->get(
            "/products",
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonMissing([
            OctoResponse::FIELD_ID => self::GROUP_PRICING_PRODUCT_ID, 
        ]);
    }

    public function test_hotelsShouldBeSkipped(): void
    {
    
        $this->tourCMSServiceMock->method('showChannel')->willReturn($this->showChannelXML);
        $this->tourCMSServiceMock->method('listTours')->willReturn($this->listToursXML);
        
        App::instance(TourCMSService::class, $this->tourCMSServiceMock);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $this->tourCMSServiceMock]);
        App::instance(ProductService::class, $productServiceMock);


        $response = $this->get(
            "/products",
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonMissing([
            OctoResponse::FIELD_ID => self::HOTEL_PRODUCT_ID 
        ]);
    }

    public function test_FreesalesShouldBeSkipped(): void
    {
    
        $this->tourCMSServiceMock->method('showChannel')->willReturn($this->showChannelXML);
        $this->tourCMSServiceMock->method('listTours')->willReturn($this->listToursXML);
        
        App::instance(TourCMSService::class, $this->tourCMSServiceMock);

        $productServiceMock = $this->getProductServiceMock(['tourCMSService' => $this->tourCMSServiceMock]);
        App::instance(ProductService::class, $productServiceMock);


        $response = $this->get(
            "/products",
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonMissing([
            OctoResponse::FIELD_ID => self::FREESALE_PRODUCT_ID, 
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