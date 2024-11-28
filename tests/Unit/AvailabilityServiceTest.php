<?php

namespace Tests\Unit;

use App\Services\AvailabilityService;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class AvailabilityServiceTest extends TestCase
{
    const START_DAY = '2024-11-30';
    const DAY_BEFORE_TO_START_DATE = '2024-11-29';

    public function setUp(): void
    {
        parent::setUp();

    }

    public function test_whenWeReceiveCutoffSecondsBefore_thenWeConvertItToOcto()
    {
        $cutoffData = [
            'type' => AvailabilityService::TCMS_CUTOFF_BEFORE_START_SECONDS,
            'value' => 3600
        ];

        $expectedCutoff = self::DAY_BEFORE_TO_START_DATE . "T23:00:00Z";

        $availabilityService = $this->getMockBuilder(AvailabilityService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        
        $octoCutoff = $availabilityService->getCutoffFromTourCMSCutoff($cutoffData, self::START_DAY);
        $this->assertEquals($expectedCutoff, $octoCutoff);
    }

    public function test_whenWeReceiveCutoffDayBeforeTime_thenWeConvertItToOcto()
    {
        $cutoffData = [
            'type' => AvailabilityService::TCMS_CUTOFF_DAY_BEFORE_TIME,
            'value' => '13:00'
        ];
        $expectedCutoff = self::DAY_BEFORE_TO_START_DATE . "T13:00:00Z";

        $availabilityService = $this->getMockBuilder(AvailabilityService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        
        $octoCutoff = $availabilityService->getCutoffFromTourCMSCutoff($cutoffData, self::START_DAY);
        $this->assertEquals($expectedCutoff, $octoCutoff);
    }

    public function test_whenWeReceiveCutoffSameDayTime_thenWeConvertItToOcto()
    {

        $cutoffData = [
            'type' => AvailabilityService::TCMS_CUTOFF_SAME_DAY_TIME,
            'value' => '13:00'
        ];
        $expectedCutoff = self::START_DAY . "T13:00:00Z";

        $availabilityService = $this->getMockBuilder(AvailabilityService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        
        $octoCutoff = $availabilityService->getCutoffFromTourCMSCutoff($cutoffData, self::START_DAY);
        $this->assertEquals($expectedCutoff, $octoCutoff);
    }
}