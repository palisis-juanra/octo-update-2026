<?php

use App\Services\DateTimeService;
use Tests\UnitTestCase;

class DateTimeServiceTest extends UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_getISODateTimeString_returnsCorrectTimezoneOffset(): void
    {
        $isoDate = DateTimeService::getISODateTimeString('09-09-2025', '12', '30', 'UTC');
        $this->assertEquals('2025-09-09T12:30:00+00:00', $isoDate);

        $timezones = ['UTC', 'Europe/Madrid', 'America/New_York', 'Africa/Johannesburg', 'Australia/Melbourne'];
        foreach ($timezones as $timezone) {
            $offset = (new DateTimeZone($timezone))->getOffset(new DateTime('2025-09-09T12:30:00+00:00'));
            $isoDate = DateTimeService::getISODateTimeString('09-09-2025', '12', '30', $timezone);
            $signCharacter = '-';
            if($offset >= 0) {
                $signCharacter = '+';
            }
            $this->assertEquals('2025-09-09T12:30:00' . $signCharacter . sprintf('%02d', abs($offset/3600)) . ':00', $isoDate);
        }
    }
}