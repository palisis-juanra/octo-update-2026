<?php

namespace Tests\Unit;

use App\Exceptions\BadRequestException;
use App\Exceptions\InvalidAvailabilityIdException;
use App\Models\Product;
use App\Services\AvailabilityService;
use App\Services\OptionService;
use App\Services\ProductService;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
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

        $validateAvailabilityIdsFunction = $this->getProtectedMethod($availabilityService, 'validateAvailabilityIds');
        $result = $validateAvailabilityIdsFunction->invokeArgs($availabilityService, [$AvailabilityIds]);
    }

    public function test_generateAvailabilityObjectFromComponent_returnsCorrectDates()
    {
        $availabilityService = $this->getMockBuilder(AvailabilityService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        
        $component = new SimpleXMLElement('<component />');
        $component->addChild('start_date', '2025-10-05');
        $component->addChild('start_time', '10:00');
        $component->addChild('end_date', '2025-10-06');
        $component->addChild('end_time', '12:00');

        $product = new Product();
        $product->setTimeZone('Europe/London');

        $availability = $availabilityService->generateAvailabilityObjectFromComponent($component, $product);
        
        $expectedLocalDateTimeStart = (string) $component->start_date . "T" . (string) $component->start_time . ':00+01:00';
        $expectedLocalDateTimeEnd = (string) $component->end_date . "T" . (string) $component->end_time . ':00+01:00';
    
        $this->assertEquals($expectedLocalDateTimeStart, $availability->getLocalDateTimeStart(), "Expected: $expectedLocalDateTimeStart, Actual:" . $availability->getLocalDateTimeStart());
        $this->assertEquals($expectedLocalDateTimeEnd, $availability->getLocalDateTimeEnd(), "Expected: $expectedLocalDateTimeEnd, Actual:" . $availability->getLocalDateTimeEnd());
    }
}