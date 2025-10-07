<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\CutoffService;
use SimpleXMLElement;
use Tests\UnitTestCase;

class CutoffServiceTest extends UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

    }

    // NO CUTOFF

    public function test_calculateCutoffForDeparture_whenCutoffValueIsZero_thenWeReturnsStartDate(): void
    {
        $product = $this->mockProduct(CutoffService::TCMS_CUTOFF_BEFORE_START_SECONDS, 0);

        $departure = new SimpleXMLElement('<root><start_time_utcseconds>1735730400</start_time_utcseconds></root>'); // 2025-01-01 11:20:00 UTC

        $cutoff = CutoffService::calculateCutoffForDeparture($product, $departure);

        $this->assertEquals('2025-01-01T11:20:00Z', $cutoff);
    }

    // BEFORE START SECONDS
    
    public function test_calculateCutoffForDeparture_whenCutoffTypeIsBeforeStartSecondsInUtc_thenWeGetCorrectUtcCutoff(): void
    {
        $product = $this->mockProduct(CutoffService::TCMS_CUTOFF_BEFORE_START_SECONDS, 3600);

        $departure = new SimpleXMLElement('<root><start_time_utcseconds>1735730400</start_time_utcseconds></root>'); // 2025-01-01 11:20:00 UTC

        $cutoff = CutoffService::calculateCutoffForDeparture($product, $departure);

        $this->assertEquals('2025-01-01T10:20:00Z', $cutoff);
    }

    public function test_calculateCutoffForDeparture_whenCutoffTypeIsBeforeStartSecondsInWestOfUtc_thenWeGetCorrectUtcCutoff(): void
    {
        $product = $this->mockProduct(CutoffService::TCMS_CUTOFF_BEFORE_START_SECONDS, 3600, 'America/New_York');

        $departure = new SimpleXMLElement('<root><start_time_utcseconds>1735730400</start_time_utcseconds></root>'); // 2025-01-01 06:20:00 America/New_York

        $cutoff = CutoffService::calculateCutoffForDeparture($product, $departure);

        $this->assertEquals('2025-01-01T10:20:00Z', $cutoff);
    }

    public function test_calculateCutoffForDeparture_whenCutoffTypeIsBeforeStartSecondsInEastOfUtc_thenWeGetCorrectUtcCutoff(): void
    {
        $product = $this->mockProduct(CutoffService::TCMS_CUTOFF_BEFORE_START_SECONDS, 3600, 'Asia/Japan');

        $departure = new SimpleXMLElement('<root><start_time_utcseconds>1735730400</start_time_utcseconds></root>'); // 2025-01-01 06:20:00 America/New_York

        $cutoff = CutoffService::calculateCutoffForDeparture($product, $departure);

        $this->assertEquals('2025-01-01T10:20:00Z', $cutoff);
    }

    // DAY BEFORE FIXED TIME

    public function test_calculateCutoffForDeparture_whenCutoffTypeIsDayBeforeAndFixedTimeInUtc_thenWeGetCorrectUtcCutoff(): void
    {
        $product = $this->mockProduct(CutoffService::TCMS_CUTOFF_DAY_BEFORE_TIME, '18:00');

        $departure = new SimpleXMLElement('<root><start_time_utcseconds>1735730400</start_time_utcseconds></root>'); // 2025-01-01 11:20:00 UTC

        $cutoff = CutoffService::calculateCutoffForDeparture($product, $departure);

        $this->assertEquals('2024-12-31T18:00:00Z', $cutoff);
    }

    public function test_calculateCutoffForDeparture_whenCutoffTypeIsDayBeforeAndFixedTimeInWestOfUtc_thenWeGetCorrectUtcCutoff(): void
    {
        $product = $this->mockProduct(CutoffService::TCMS_CUTOFF_DAY_BEFORE_TIME, '18:00', 'America/New_York');

        $departure = new SimpleXMLElement('<root><start_time_utcseconds>1735730400</start_time_utcseconds></root>'); // 2025-01-01 06:20:00 America/New_York

        $cutoff = CutoffService::calculateCutoffForDeparture($product, $departure);

        $this->assertEquals('2024-12-31T23:00:00Z', $cutoff);
    }

    public function test_calculateCutoffForDeparture_whenCutoffTypeIsDayBeforeAndFixedTimeInEastOfUtc_thenWeGetCorrectUtcCutoff(): void
    {
        $product = $this->mockProduct(CutoffService::TCMS_CUTOFF_DAY_BEFORE_TIME, '18:00', 'Asia/Tokyo');

        $departure = new SimpleXMLElement('<root><start_time_utcseconds>1735730400</start_time_utcseconds></root>'); // 2025-01-01 06:20:00 Asia/Tokyo

        $cutoff = CutoffService::calculateCutoffForDeparture($product, $departure);

        $this->assertEquals('2024-12-31T09:00:00Z', $cutoff);
    }

    // SAME DAY FIXED TIME
    public function test_calculateCutoffForDeparture_whenCutoffTypeIsSameDayFixedTimeInUtc_thenWeGetCorrectUtcCutoff(): void
    {
        $product = $this->mockProduct(CutoffService::TCMS_CUTOFF_SAME_DAY_TIME, '18:00');

        $departure = new SimpleXMLElement('<root><start_time_utcseconds>1735730400</start_time_utcseconds></root>'); // 2025-01-01 11:20:00 UTC

        $cutoff = CutoffService::calculateCutoffForDeparture($product, $departure);

        $this->assertEquals('2025-01-01T18:00:00Z', $cutoff);
    }

    public function test_calculateCutoffForDeparture_whenCutoffTypeIsSameDayFixedTimeInWestOfUtc_thenWeGetCorrectUtcCutoff(): void
    {
        $product = $this->mockProduct(CutoffService::TCMS_CUTOFF_SAME_DAY_TIME, '18:00', 'America/New_York');

        $departure = new SimpleXMLElement('<root><start_time_utcseconds>1735730400</start_time_utcseconds></root>'); // 2025-01-01 06:20:00 America/New_York

        $cutoff = CutoffService::calculateCutoffForDeparture($product, $departure);

        $this->assertEquals('2025-01-01T23:00:00Z', $cutoff);
    }

    public function test_calculateCutoffForDeparture_whenCutoffTypeIsSameDayFixedTimeInEastOfUtc_thenWeGetCorrectUtcCutoff(): void
    {
        $product = $this->mockProduct(CutoffService::TCMS_CUTOFF_SAME_DAY_TIME, '18:00', 'Asia/Tokyo');

        $departure = new SimpleXMLElement('<root><start_time_utcseconds>1735730400</start_time_utcseconds></root>'); // 2025-01-01 20:20:00 Asia/Tokyo

        $cutoff = CutoffService::calculateCutoffForDeparture($product, $departure);

        $this->assertEquals('2025-01-01T09:00:00Z', $cutoff);
    }

    // PROTECTED METHODS

    protected function mockProduct(string $cutoffType, int|string $cutoffValue, string $timezone = 'UTC')
    {
        $cutoff = [
            'type' => $cutoffType,
            'value' => $cutoffValue
        ];

        $product = new Product();
        $product
            ->setCutoff($cutoff)
            ->setTimeZone($timezone);

        return $product;
    }
}