<?php

use App\Exceptions\InvalidUnitIdException;
use App\Services\UnitService;
use Tests\UnitTestCase;

class UnitServiceTest extends UnitTestCase
{
    public function test_get_tour_cms_rate_id_with_valid_unit_id(): void
    {
        $unitId = 'TE_1_67|142|r1';
        $expectedRateId = 'r1';

        $result = UnitService::getTourCMSRateId($unitId);

        $this->assertEquals($expectedRateId, $result);
    }

    public function test_get_tour_cms_rate_id_with_invalid_unit_id(): void
    {
        $unitId = 'TE_1_67|r1';

        $this->expectException(InvalidUnitIdException::class);
        UnitService::getTourCMSRateId($unitId);

    }
}
