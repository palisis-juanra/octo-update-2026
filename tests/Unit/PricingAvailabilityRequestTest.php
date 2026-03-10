<?php

use App\Exceptions\InvalidUnitIdException;
use App\Features\Availability\Pricing\PricingAvailabilityRequest;
use App\Models\Product;
use App\Services\RateService;
use Tests\UnitTestCase;

class PricingAvailabilityRequestTest extends UnitTestCase
{
    protected $rateServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->rateServiceMock = $this->getMockBuilder(RateService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }
    public function test_generateRatesParamsFromUnits_withSingleUnit(): void
    {
        
        $units = [
            [
                'id' => 'TE_1_67|142|r1',
                'quantity' => 3
            ]
        ];

        $pricingAvailabilityRequest = new PricingAvailabilityRequest($this->rateServiceMock, $this->getProduct(1), "SINGLE", "2023-10-01", $units, "USD", 1, 5);

        $expectedParams = "r1=3";
        
        $params = $pricingAvailabilityRequest->generateRatesParamsFromUnits($units);

        $this->assertEquals($expectedParams, $params);
    }

    public function test_generateRatesParamsFromUnits_withMultipleUnits(): void
    {
        
        $units = [
            [
                'id' => 'TE_1_67|142|r1',
                'quantity' => 2
            ],
            [
                'id' => 'TE_1_67|142|r2',
                'quantity' => 1
            ]
        ];

        $pricingAvailabilityRequest = new PricingAvailabilityRequest($this->rateServiceMock, $this->getProduct(1), "SINGLE", "2023-10-01", $units, "USD", 1, 5);

        $expectedParams = "r1=2&r2=1";
        
        $params = $pricingAvailabilityRequest->generateRatesParamsFromUnits($units);

        $this->assertEquals($expectedParams, $params);
    }

    public function test_generateRatesParamsFromUnits_withoutUnits(): void
    {
        
        $units = [
            [
                // Invalid Format
                'id' => 'TE_1_67|r1',
                'quantity' => 2
            ],
        ];
        $minBookingSize = 1;
        
        $pricingAvailabilityRequest = new PricingAvailabilityRequest($this->rateServiceMock, $this->getProduct(1), "SINGLE", "2023-10-01", $units, "USD", $minBookingSize, 5);

        $this->expectException(InvalidUnitIdException::class);
        
        $pricingAvailabilityRequest->generateRatesParamsFromUnits($units);

    }

    public function test_generateRatesParamsFromUnits_withInvalidUnits(): void
    {
        
        $units = [];
        $minBookingSize = 1;
        
        $pricingAvailabilityRequest = new PricingAvailabilityRequest($this->rateServiceMock, $this->getProduct(1), "SINGLE", "2023-10-01", $units, "USD", $minBookingSize, 5);

        // If we dont have units, we set the quantity to the minimum booking size
        $expectedParams = "r1={$minBookingSize}";
        
        $params = $pricingAvailabilityRequest->generateRatesParamsFromUnits($units);

        $this->assertEquals($expectedParams, $params);
    }

    protected function getProduct(int $tourId): Product
    {
        $product = new Product();
        
        return $product->setId($tourId);
    }
}