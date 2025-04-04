<?php

use App\Exceptions\InvalidUnitIdException;
use App\Features\Availability\Pricing\PricingAvailabilityRequest;
use Tests\UnitTestCase;

class PricingAvailabilityRequestTest extends UnitTestCase
{
    public function test_generateRatesParamsFromUnits_withSingleUnit(): void
    {
        
        $units = [
            [
                'id' => 'TE_1_67|142|r1',
                'quantity' => 3
            ]
        ];

        $pricingAvailabilityRequest = new PricingAvailabilityRequest("1", "SINGLE", "2023-10-01", $units, "USD", 1, 5);

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

        $pricingAvailabilityRequest = new PricingAvailabilityRequest("1", "SINGLE", "2023-10-01", $units, "USD", 1, 5);

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
        
        $pricingAvailabilityRequest = new PricingAvailabilityRequest("1", "SINGLE", "2023-10-01", $units, "USD", $minBookingSize, 5);

        $this->expectException(InvalidUnitIdException::class);
        
        $pricingAvailabilityRequest->generateRatesParamsFromUnits($units);

    }

    public function test_generateRatesParamsFromUnits_withInvalidUnits(): void
    {
        
        $units = [];
        $minBookingSize = 1;
        
        $pricingAvailabilityRequest = new PricingAvailabilityRequest("1", "SINGLE", "2023-10-01", $units, "USD", $minBookingSize, 5);

        // If we dont have units, we set the quantity to the minimum booking size
        $expectedParams = "r1={$minBookingSize}";
        
        $params = $pricingAvailabilityRequest->generateRatesParamsFromUnits($units);

        $this->assertEquals($expectedParams, $params);
    }
}