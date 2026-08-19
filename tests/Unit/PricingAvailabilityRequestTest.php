<?php

use App\Exceptions\InvalidUnitIdException;
use App\Features\Availability\Pricing\PricingAvailabilityRequest;
use App\Models\Product;
use App\Services\AvailabilityPromotionService;
use Tests\UnitTestCase;

class PricingAvailabilityRequestTest extends UnitTestCase
{
    protected $availabilityPromotionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availabilityPromotionService = $this->getMockBuilder(AvailabilityPromotionService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    public function test_generate_rates_params_from_units_with_single_unit(): void
    {

        $units = [
            [
                'id' => 'TE_1_67|142|r1',
                'quantity' => 3,
            ],
        ];

        $pricingAvailabilityRequest = new PricingAvailabilityRequest($this->availabilityPromotionService, $this->getProduct(1), 'SINGLE', '2023-10-01', $units, 'USD');

        $expectedParams = 'r1=3';

        $params = $pricingAvailabilityRequest->generateRatesParamsFromUnits($units);

        $this->assertEquals($expectedParams, $params);
    }

    public function test_generate_rates_params_from_units_with_multiple_units(): void
    {

        $units = [
            [
                'id' => 'TE_1_67|142|r1',
                'quantity' => 2,
            ],
            [
                'id' => 'TE_1_67|142|r2',
                'quantity' => 1,
            ],
        ];

        $pricingAvailabilityRequest = new PricingAvailabilityRequest($this->availabilityPromotionService, $this->getProduct(1), 'SINGLE', '2023-10-01', $units, 'USD');

        $expectedParams = 'r1=2&r2=1';

        $params = $pricingAvailabilityRequest->generateRatesParamsFromUnits($units);

        $this->assertEquals($expectedParams, $params);
    }

    public function test_generate_rates_params_from_units_without_units(): void
    {

        $units = [
            [
                // Invalid Format
                'id' => 'TE_1_67|r1',
                'quantity' => 2,
            ],
        ];
        $minBookingSize = 1;

        $pricingAvailabilityRequest = new PricingAvailabilityRequest($this->availabilityPromotionService, $this->getProduct(1), 'SINGLE', '2023-10-01', $units, 'USD');

        $this->expectException(InvalidUnitIdException::class);

        $pricingAvailabilityRequest->generateRatesParamsFromUnits($units);

    }

    public function test_generate_rates_params_from_units_with_invalid_units(): void
    {

        $units = [];
        $minBookingSize = 1;

        $pricingAvailabilityRequest = new PricingAvailabilityRequest($this->availabilityPromotionService, $this->getProduct(1), 'SINGLE', '2023-10-01', $units, 'USD');

        // If we dont have units, we set the quantity to the minimum booking size
        $expectedParams = "r1={$minBookingSize}";

        $params = $pricingAvailabilityRequest->generateRatesParamsFromUnits($units);

        $this->assertEquals($expectedParams, $params);
    }

    protected function getProduct(int $tourId): Product
    {
        $product = new Product;
        $product->setTourId($tourId);

        return $product->setId($tourId);
    }
}
