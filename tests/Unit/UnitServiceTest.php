<?php

use App\Exceptions\InvalidUnitIdException;
use App\Services\UnitService;
use Tests\UnitTestCase;

class UnitServiceTest extends UnitTestCase
{
    public function test_getTourCMSRateId_withValidUnitId(): void
    {
        $unitId = 'TE_1_67|142|r1';
        $expectedRateId = 'r1';

        $result = UnitService::getTourCMSRateId($unitId);

        $this->assertEquals($expectedRateId, $result);
    }

    public function test_getTourCMSRateId_withInvalidUnitId(): void
    {
        $unitId = 'TE_1_67|r1';

        $this->expectException(InvalidUnitIdException::class);
        UnitService::getTourCMSRateId($unitId);

    }
}