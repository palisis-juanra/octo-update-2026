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
        $isoDate = DateTimeService::getISODateTimeString('05-09-2025', '12', '30', 'UTC');
        $this->assertEquals('2025-09-05T12:30:00+00:00', $isoDate);

        $timezones = ['UTC', 'Europe/Madrid', 'Asia/Japan', 'America/New_York', 'America/Argentina', 'Africa/Johannesburg', 'Australia/Melbourne'];
        foreach ($timezones as $timezone) {
            $offset = (new DateTimeZone('Europe/Madrid'))->getOffset(new DateTime('now', new DateTimeZone('UTC')));
            $isoDate = DateTimeService::getISODateTimeString('05-09-2025', '12', '30', 'Europe/Madrid');
            $this->assertEquals('2025-09-05T12:30:00+' . sprintf('%02d', $offset/3600) . ':00', $isoDate);
        }
    }
}