<?php

namespace Tests\Unit;

use App\Features\Availability\AvailabilityRequest;
use App\Features\Availability\Pricing\PricingAvailabilityRequest;
use App\Models\Availability\Availability;
use App\Models\Availability\AvailabilityUnitPricing;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\TourCMS\Promotion;
use App\Services\AvailabilityPromotionService;
use DateTime;
use ReflectionProperty;
use SimpleXMLElement;
use Tests\UnitTestCase;

/**
 * Regression tests for the availability pricing discrepancies reported against
 * the /availability endpoint, where unitPricing disagreed with the top level
 * pricing block for the same rate.
 *
 * Two distinct defects are covered:
 *  - Prices in cents were truncated instead of rounded when the float to int
 *    cast happened, losing a cent (e.g. 3280 became 3279).
 *  - The promotion discount was applied twice to unitPricing on the pricing
 *    request path, because enrichment ran both in the parent and the subclass.
 */
class AvailabilityPricingRoundingTest extends UnitTestCase
{
    /**
     * 32.80 is not exactly representable in binary floating point: 32.80 * 100
     * evaluates to 3279.99999999999954..., so a plain (int) cast truncates to
     * 3279. 24.60 * 100 lands on exactly 2460, which is why the original bug
     * report showed net matching while retail/original were off by one.
     */
    private const RATE_PRICE_R1 = '32.80';
    private const NET_PRICE_R1 = '24.60';
    private const EXPECTED_RETAIL_R1 = 3280;
    private const EXPECTED_NET_R1 = 2460;

    public function test_getUnitPricingFromShowTourDeparture_multipleRates_roundsPriceInsteadOfTruncating(): void
    {
        $availabilityRequest = $this->makeAvailabilityRequest();

        $unitPricings = $this->invokeGetUnitPricing($availabilityRequest, $this->makeDeparture());

        $r1 = $this->findUnitPricing($unitPricings, 'GL_1_1|5879|r1');

        $this->assertSame(self::EXPECTED_RETAIL_R1, $r1->getOriginalPrice());
        $this->assertSame(self::EXPECTED_RETAIL_R1, $r1->getRetailPrice());
        $this->assertSame(self::EXPECTED_NET_R1, $r1->getNetPrice());
    }

    public function test_getUnitPricingFromShowTourDeparture_volumePricing_roundsPriceInsteadOfTruncating(): void
    {
        $units = [['id' => 'GL_1_1|5879|r1', 'quantity' => 1]];
        $availabilityRequest = $this->makeAvailabilityRequest($units, Product::PRICING_TYPE_VOLUME);

        $unitPricings = $this->invokeGetUnitPricing($availabilityRequest, $this->makeDeparture());

        $this->assertCount(1, $unitPricings);
        $this->assertSame(self::EXPECTED_RETAIL_R1, $unitPricings[0]->getOriginalPrice());
        $this->assertSame(self::EXPECTED_RETAIL_R1, $unitPricings[0]->getRetailPrice());
        $this->assertSame(self::EXPECTED_NET_R1, $unitPricings[0]->getNetPrice());
    }

    public function test_getPricingForShowTourDeparture_volumePricing_roundsPriceInsteadOfTruncating(): void
    {
        $units = [['id' => 'GL_1_1|5879|r1', 'quantity' => 1]];
        $availabilityRequest = $this->makeAvailabilityRequest($units, Product::PRICING_TYPE_VOLUME);

        $pricing = $this->invokeGetPricing($availabilityRequest, $this->makeDeparture());

        $this->assertSame(self::EXPECTED_RETAIL_R1, $pricing->getOriginal());
        $this->assertSame(self::EXPECTED_RETAIL_R1, $pricing->getRetail());
        $this->assertSame(self::EXPECTED_NET_R1, $pricing->getNet());
    }

    /**
     * The reported symptom: for a single unit of r1 the top level pricing and
     * the matching unitPricing entry must agree.
     */
    public function test_pricingAndUnitPricing_singleUnitOfR1_agreeOnEveryField(): void
    {
        $units = [['id' => 'GL_1_1|5879|r1', 'quantity' => 1]];
        $availabilityRequest = $this->makeAvailabilityRequest($units);
        $departure = $this->makeDeparture();

        $pricing = $this->invokeGetPricing($availabilityRequest, $departure);
        $r1 = $this->findUnitPricing(
            $this->invokeGetUnitPricing($availabilityRequest, $departure),
            'GL_1_1|5879|r1'
        );

        $this->assertSame($pricing->getOriginal(), $r1->getOriginalPrice());
        $this->assertSame($pricing->getRetail(), $r1->getRetailPrice());
        $this->assertSame($pricing->getNet(), $r1->getNetPrice());
    }

    /**
     * A date range request carries no units, and the multi rate branch used to
     * iterate an empty list and return 0/0/0 while unitPricing was populated.
     * With no explicit selection we price the minimum booking size of r1, the
     * same default generateRatesParamsFromUnits() sends to check availability.
     */
    public function test_getPricingForShowTourDeparture_multipleRatesWithoutUnits_pricesMinimumBookingSizeOfR1(): void
    {
        $availabilityRequest = $this->makeAvailabilityRequest();

        $pricing = $this->invokeGetPricing($availabilityRequest, $this->makeDeparture());

        $this->assertSame(self::EXPECTED_RETAIL_R1, $pricing->getOriginal());
        $this->assertSame(self::EXPECTED_RETAIL_R1, $pricing->getRetail());
        $this->assertSame(self::EXPECTED_NET_R1, $pricing->getNet());
    }

    public function test_getPricingForShowTourDeparture_multipleRatesWithoutUnits_multipliesByMinimumBookingSize(): void
    {
        $availabilityRequest = $this->makeAvailabilityRequest();
        $availabilityRequest->getProduct()->setMinBookingSize(2);

        $pricing = $this->invokeGetPricing($availabilityRequest, $this->makeDeparture());

        $this->assertSame(self::EXPECTED_RETAIL_R1 * 2, $pricing->getRetail());
        $this->assertSame(self::EXPECTED_NET_R1 * 2, $pricing->getNet());
    }

    /**
     * The reported live symptom: a date range request returned a zeroed pricing
     * block next to a correctly priced unitPricing entry.
     */
    public function test_pricingAndUnitPricing_dateRangeWithoutUnits_agreeOnR1(): void
    {
        $availabilityRequest = $this->makeAvailabilityRequest();
        $departure = $this->makeDeparture();

        $pricing = $this->invokeGetPricing($availabilityRequest, $departure);
        $r1 = $this->findUnitPricing(
            $this->invokeGetUnitPricing($availabilityRequest, $departure),
            'GL_1_1|5879|r1'
        );

        $this->assertSame($r1->getRetailPrice(), $pricing->getRetail());
        $this->assertSame($r1->getNetPrice(), $pricing->getNet());
    }

    public function test_isPromotionEnrichmentEnabled_plainAvailabilityRequest_isEnabled(): void
    {
        $availabilityRequest = $this->makeAvailabilityRequest();

        $method = $this->getProtectedMethod($availabilityRequest, 'isPromotionEnrichmentEnabled');

        $this->assertTrue($method->invoke($availabilityRequest));
    }

    /**
     * PricingAvailabilityRequest runs enrichment itself after replacing the
     * pricing block with check_tour_availability values, so the inherited
     * enrichment must stay off to avoid discounting unitPricing twice.
     */
    public function test_isPromotionEnrichmentEnabled_pricingAvailabilityRequest_isDisabled(): void
    {
        $pricingAvailabilityRequest = new PricingAvailabilityRequest(
            $this->makePromotionServiceMock(),
            $this->makeProduct(),
            'SUPPLIER_NOTE|X',
            '2026-10-08',
            [],
            'EUR'
        );

        $method = $this->getProtectedMethod($pricingAvailabilityRequest, 'isPromotionEnrichmentEnabled');

        $this->assertFalse($method->invoke($pricingAvailabilityRequest));
    }

    /**
     * Guards the reason isPromotionEnrichmentEnabled() exists: applyPromotionDiscount
     * mutates unitPricing in place, so a second enrichment pass compounds the
     * discount (3280 -> 2952 -> 2657) rather than being idempotent.
     */
    public function test_enrichAvailabilityWithPromotions_appliedTwice_compoundsTheDiscount(): void
    {
        $promotion = new Promotion('GENIUS1', 10.0, new DateTime('2026-01-01'), new DateTime('2026-12-31'));
        $product = $this->makeProduct();
        $product->setPromotions([$promotion]);

        $service = new AvailabilityPromotionService($this->getLoggerMock());

        $availability = $this->makeAvailability();
        $service->enrichAvailabilityWithPromotions($product, $availability, $promotion);

        $this->assertSame(2952, $availability->getUnitPricing()[0]->getRetailPrice(), 'first pass applies 10% once');

        $service->enrichAvailabilityWithPromotions($product, $availability, $promotion);

        $this->assertSame(2657, $availability->getUnitPricing()[0]->getRetailPrice(), 'second pass compounds the discount');
    }

    private function makeAvailability(): Availability
    {
        $unitPricing = (new AvailabilityUnitPricing())
            ->setUnitId('GL_1_1|5879|r1')
            ->setOriginalPrice(self::EXPECTED_RETAIL_R1)
            ->setRetailPrice(self::EXPECTED_RETAIL_R1)
            ->setNetPrice(self::EXPECTED_NET_R1)
            ->setCurrency('EUR');

        $availability = new Availability();
        $availability->setId('2026-10-08|1585169');
        $availability->setLocalDateTimeStart('2026-10-08T08:30:00+02:00');
        $availability->setPricing(new Pricing(self::EXPECTED_RETAIL_R1, self::EXPECTED_RETAIL_R1, self::EXPECTED_NET_R1, 'EUR'));
        $availability->setUnitPricing([$unitPricing]);

        return $availability;
    }

    private function makeDeparture(): SimpleXMLElement
    {
        $departure = new SimpleXMLElement('<departure />');

        $mainPrice = $departure->addChild('main_price');
        $mainPrice->addChild('rate_price', self::RATE_PRICE_R1);
        $mainPrice->addChild('net_price', self::NET_PRICE_R1);

        $extraRates = $departure->addChild('extra_rates');
        $r2 = $extraRates->addChild('rate');
        $r2->addChild('rate_id', 'r2');
        $r2->addChild('rate_price', '20.00');
        $r2->addChild('net_price', '15.00');

        return $departure;
    }

    private function makeProduct(string $pricingType = Product::PRICING_TYPE_MULTIPLE_RATES): Product
    {
        $product = new Product();
        $product->setTourId(5879);
        $product->setId('GL_1_1|5879');
        $product->setPricingType($pricingType);
        $product->setMinBookingSize(1);
        $product->setMaxBookingSize(20);

        return $product;
    }

    private function makePromotionServiceMock(): AvailabilityPromotionService
    {
        return $this->getMockBuilder(AvailabilityPromotionService::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function makeAvailabilityRequest(
        array $units = [],
        string $pricingType = Product::PRICING_TYPE_MULTIPLE_RATES
    ): AvailabilityRequest {
        $availabilityRequest = new AvailabilityRequest(
            $this->makePromotionServiceMock(),
            $this->makeProduct($pricingType),
            'SUPPLIER_NOTE|X',
            '2026-10-08',
            '2026-10-08'
        );
        $availabilityRequest->setUnits($units);

        $currency = new ReflectionProperty($availabilityRequest, 'currency');
        $currency->setAccessible(true);
        $currency->setValue($availabilityRequest, 'EUR');

        return $availabilityRequest;
    }

    /**
     * @return AvailabilityUnitPricing[]
     */
    private function invokeGetUnitPricing(AvailabilityRequest $request, SimpleXMLElement $departure): array
    {
        return $this->getProtectedMethod($request, 'getUnitPricingFromShowTourDeparture')
            ->invokeArgs($request, [$departure]);
    }

    private function invokeGetPricing(AvailabilityRequest $request, SimpleXMLElement $departure): Pricing
    {
        return $this->getProtectedMethod($request, 'getPricingForShowTourDeparture')
            ->invokeArgs($request, [$departure]);
    }

    /**
     * @param AvailabilityUnitPricing[] $unitPricings
     */
    private function findUnitPricing(array $unitPricings, string $unitId): AvailabilityUnitPricing
    {
        foreach ($unitPricings as $unitPricing) {
            if ($unitPricing->getUnitId() === $unitId) {
                return $unitPricing;
            }
        }

        $this->fail("No unitPricing entry found for {$unitId}");
    }
}
