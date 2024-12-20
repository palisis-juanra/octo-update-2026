<?php

namespace Tests\Unit;

use App\Factories\AvailabilityRequestFactory;
use App\Features\Availability\AvailabilityRequest;
use App\Features\Availability\Pricing\MultiDayPricingAvailabilityRequest;
use App\Features\Availability\Pricing\PricingAvailabilityRequest;
use App\Features\Availability\Pricing\SingleDayPricingAvailabilityRequest;
use App\Interfaces\BaseAvailabilityRequest;
use App\Services\ProductService;
use Tests\UnitTestCase;

class AvailabilityRequestFactoryTest extends UnitTestCase
{
    public $productServiceMock;
    public AvailabilityRequestFactory $factory;
    const PRICING_HEADER = 'pricing';
    
    public function setUp(): void
    {
        parent::setUp();

        $this->productServiceMock = $this->getMockBuilder(ProductService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTourIdFromProductId'])
            ->getMock();
        $this->productServiceMock->method('getTourIdFromProductId')->willReturn('67');
        $this->factory = new AvailabilityRequestFactory($this->productServiceMock);
    }

    public function test_whenRequestHasOnlyOneDayWithoutPricingHeader_thenAvailabilityRequestIsCreated()
    {
        $requestParams = [
            "productId" => "TE_1_67|142",
            "optionId" => "START_TIME|13:00",
            "localDate" => "2024-11-28",
        ];
        
        $availabilityRequest = $this->factory->get($requestParams, '');

        $this->assertInstanceOf(BaseAvailabilityRequest::class, $availabilityRequest);
        $this->assertInstanceOf(AvailabilityRequest::class, $availabilityRequest);
        $this->assertEquals($requestParams["localDate"], $availabilityRequest->getLocalDateStart());
        $this->assertEquals('', $availabilityRequest->getLocalDateEnd());

    }

    public function test_whenRequestHasAPeriodOfTimeWithoutPricingHeader_thenAvailability_thenAvailabilityRequestIsCreated()
    {
        $requestParams = [
            "productId" => "TE_1_67|142",
            "optionId" => "START_TIME|13:00",
            "localDateStart" => "2024-11-18",
            "localDateEnd" => "2024-11-25",
        ];

        $availabilityRequest = $this->factory->get($requestParams, '');

        $this->assertInstanceOf(BaseAvailabilityRequest::class, $availabilityRequest);
        $this->assertInstanceOf(AvailabilityRequest::class, $availabilityRequest);
        $this->assertEquals($requestParams["localDateStart"], $availabilityRequest->getLocalDateStart());
        $this->assertEquals($requestParams["localDateEnd"], $availabilityRequest->getLocalDateEnd());
    }

    public function test_whenRequestHasAOnlyOneDayWithPricingHeader_thenSingleDayAvailabilityRequestIsCreated()
    {
        $requestParams = [
            "productId" => "TE_1_67|142",
            "optionId" => "START_TIME|13:00",
            "localDate" => "2024-11-28",
        ];

        $availabilityRequest = $this->factory->get($requestParams, self::PRICING_HEADER);

        $this->assertInstanceOf(BaseAvailabilityRequest::class, $availabilityRequest);
        $this->assertInstanceOf(PricingAvailabilityRequest::class, $availabilityRequest);
        $this->assertInstanceOf(SingleDayPricingAvailabilityRequest::class, $availabilityRequest);
        $this->assertEquals($requestParams["localDate"], $availabilityRequest->getLocalDateStart());
    }

    public function test_whenRequestHasAPeriodOfTimeWithPricingHeader_thenMultiDayAvailabilityRequestIsCreated()
    {
        $requestParams = [
            "productId" => "TE_1_67|142",
            "optionId" => "START_TIME|13:00",
            "localDateStart" => "2024-11-18",
            "localDateEnd" => "2024-11-25",
        ];

        $availabilityRequest = $this->factory->get($requestParams, self::PRICING_HEADER);

        $this->assertInstanceOf(BaseAvailabilityRequest::class, $availabilityRequest);
        $this->assertInstanceOf(PricingAvailabilityRequest::class, $availabilityRequest);
        $this->assertInstanceOf(MultiDayPricingAvailabilityRequest::class, $availabilityRequest);
        $this->assertEquals($requestParams["localDateStart"], $availabilityRequest->getLocalDateStart());
        $this->assertEquals($requestParams["localDateEnd"], $availabilityRequest->getLocalDateEnd());
    }
}