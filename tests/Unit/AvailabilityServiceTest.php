<?php

namespace Tests\Unit;

use App\Exceptions\BadRequestException;
use App\Exceptions\InvalidAvailabilityIdException;
use App\Services\AvailabilityService;
use App\Services\OptionService;
use App\Services\ProductService;
use PHPUnit\Framework\TestCase;
use Tests\UnitTestCase;

class AvailabilityServiceTest extends UnitTestCase
{
    const START_DAY = '2024-11-30';
    const DAY_BEFORE_TO_START_DATE = '2024-11-29';
    const VALID_AVAILABILITY_ID = '2024-11-30|1234';
    const INVALID_AVAILABILITY_ID = '2024-11-32_1234c';

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

    public function test_validateAvailabilityIds_WhenAvailabilityIdDoesNotMatchRegEx_thenThrowsInvalidAvailabilityIdException()
    {
        $availabilityService = $this->getMockBuilder(AvailabilityService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $AvailabilityIds = [
            self::VALID_AVAILABILITY_ID,
            self::INVALID_AVAILABILITY_ID,
        ];

        $this->expectException(InvalidAvailabilityIdException::class);

        $availabilityService->validateAvailabilityIds($AvailabilityIds);
    }
}