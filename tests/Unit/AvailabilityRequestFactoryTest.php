<?php

namespace Tests\Unit;

use App\Facades\OctoRequestFacade;
use App\Factories\AvailabilityRequestFactory;
use App\Features\Availability\AvailabilityRequest;
use App\Features\Availability\Pricing\PricingAvailabilityRequest;
use App\Http\Requests\OctoRequest;
use App\Interfaces\BaseAvailabilityRequest;
use App\Models\Product;
use App\Services\AvailabilityPromotionService;
use App\Services\ProductService;
use App\Services\TourPromotionService;
use Tests\FeatureTestCase;

class AvailabilityRequestFactoryTest extends FeatureTestCase
{
    public $tourPromotionServiceMock;

    public $productServiceMock;

    public $availabilityPromotionServiceMock;

    public AvailabilityRequestFactory $factory;

    public const PRICING_HEADER = 'pricing';

    protected function setUp(): void
    {
        parent::setUp();

        $this->productServiceMock = $this->getMockBuilder(ProductService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTourIdFromProductId'])
            ->getMock();
        $this->productServiceMock->method('getTourIdFromProductId')->willReturn('67');

        $this->tourPromotionServiceMock = $this->getMockBuilder(TourPromotionService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->availabilityPromotionServiceMock = $this->getMockBuilder(AvailabilityPromotionService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->factory = new AvailabilityRequestFactory($this->productServiceMock, $this->tourPromotionServiceMock, $this->availabilityPromotionServiceMock);
    }

    public function test_when_request_has_only_one_day_without_pricing_header_then_availability_request_is_created(): void
    {
        $requestParams = [
            'productId' => 'TE_1_67|142',
            'optionId' => 'START_TIME|13:00',
            'localDate' => '2024-11-28',
        ];

        $availabilityRequest = $this->factory->get($this->getProduct($requestParams['productId']), $requestParams);

        $this->assertInstanceOf(BaseAvailabilityRequest::class, $availabilityRequest);
        $this->assertInstanceOf(AvailabilityRequest::class, $availabilityRequest);
        $this->assertEquals($requestParams['localDate'], $availabilityRequest->getLocalDateStart());
        $this->assertEquals('', $availabilityRequest->getLocalDateEnd());

    }

    public function test_when_request_has_a_period_of_time_without_pricing_header_then_availability_then_availability_request_is_created(): void
    {
        $requestParams = [
            'productId' => 'TE_1_67|142',
            'optionId' => 'START_TIME|13:00',
            'localDateStart' => '2024-11-18',
            'localDateEnd' => '2024-11-25',
        ];

        $availabilityRequest = $this->factory->get($this->getProduct($requestParams['productId']), $requestParams);

        $this->assertInstanceOf(BaseAvailabilityRequest::class, $availabilityRequest);
        $this->assertInstanceOf(AvailabilityRequest::class, $availabilityRequest);
        $this->assertEquals($requestParams['localDateStart'], $availabilityRequest->getLocalDateStart());
        $this->assertEquals($requestParams['localDateEnd'], $availabilityRequest->getLocalDateEnd());
    }

    public function test_when_request_has_a_only_one_day_with_pricing_header_then_pricing_availability_request_is_created(): void
    {
        OctoRequestFacade::shouldReceive('isCapabilityActive')
            ->andReturn(true);

        $requestParams = [
            'productId' => 'TE_1_67|142',
            'optionId' => 'START_TIME|13:00',
            'localDate' => '2024-11-28',
        ];

        $availabilityRequest = $this->factory->get($this->getProduct($requestParams['productId']), $requestParams);

        $this->assertInstanceOf(BaseAvailabilityRequest::class, $availabilityRequest);
        $this->assertInstanceOf(PricingAvailabilityRequest::class, $availabilityRequest);
        $this->assertEquals($requestParams['localDate'], $availabilityRequest->getLocalDateStart());
    }

    public function test_when_request_has_multiple_availability_ids_then_local_date_start_and_local_date_end_match_min_and_max_date(): void
    {
        $requestParams = [
            'productId' => 'TE_1_67|142',
            'optionId' => 'START_TIME|13:00',
            'availabilityIds' => [
                '2024-11-18|1234',
                '2024-11-20|1235',
                '2024-11-25|1236',
            ],
        ];

        $availabilityIdsDates = [];
        foreach ($requestParams['availabilityIds'] as $availabilityId) {
            $availabilityIdsDates[] = explode('|', $availabilityId)[0];
        }

        $availabilityRequest = $this->factory->get($this->getProduct($requestParams['productId']), $requestParams);

        $this->assertEquals(min($availabilityIdsDates), $availabilityRequest->getLocalDateStart());
        $this->assertEquals(max($availabilityIdsDates), $availabilityRequest->getLocalDateEnd());
    }

    public function test_when_request_has_only_one_availability_id_then_local_date_start_match_availability_id_date(): void
    {
        $requestParams = [
            'productId' => 'TE_1_67|142',
            'optionId' => 'START_TIME|13:00',
            'availabilityIds' => [
                '2024-11-18|1234',
            ],
        ];

        $availabilityIdDate = explode('|', $requestParams['availabilityIds'][0])[0];

        $availabilityRequest = $this->factory->get($this->getProduct($requestParams['productId']), $requestParams);

        $this->assertEquals($availabilityIdDate, $availabilityRequest->getLocalDateStart());
    }

    public function test_when_request_has_only_one_availability_id_with_pricing_header_then_pricing_availability_request_is_created(): void
    {
        $this->withHeaders([
            OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING,
        ]);

        OctoRequestFacade::shouldReceive('isCapabilityActive')
            ->andReturn(true);

        $requestParams = [
            'productId' => 'TE_1_67|142',
            'optionId' => 'START_TIME|13:00',
            'availabilityIds' => [
                '2024-11-18|1234',
            ],
        ];

        $availabilityIdDate = explode('|', $requestParams['availabilityIds'][0])[0];

        $availabilityRequest = $this->factory->get($this->getProduct($requestParams['productId']), $requestParams);

        $this->assertInstanceOf(BaseAvailabilityRequest::class, $availabilityRequest);
        $this->assertInstanceOf(PricingAvailabilityRequest::class, $availabilityRequest);
        $this->assertEquals($availabilityIdDate, $availabilityRequest->getLocalDateStart());
    }

    public function test_when_request_has_only_one_availability_id_without_pricing_header_then_single_day_availability_request_is_created(): void
    {
        $requestParams = [
            'productId' => 'TE_1_67|142',
            'optionId' => 'START_TIME|13:00',
            'availabilityIds' => [
                '2024-11-18|1234',
            ],
        ];

        $availabilityIdDate = explode('|', $requestParams['availabilityIds'][0])[0];

        $availabilityRequest = $this->factory->get($this->getProduct($requestParams['productId']), $requestParams);

        $this->assertInstanceOf(BaseAvailabilityRequest::class, $availabilityRequest);
        $this->assertInstanceOf(AvailabilityRequest::class, $availabilityRequest);
        $this->assertEquals($availabilityIdDate, $availabilityRequest->getLocalDateStart());
    }

    public function getProduct(string $productId): Product
    {
        $product = new Product;

        $productServiceMock = $this->getMockBuilder(ProductService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $tourId = $productServiceMock->getTourIdFromProductId($productId);

        return $product->setId($productId)->setTourId($tourId);
    }
}
