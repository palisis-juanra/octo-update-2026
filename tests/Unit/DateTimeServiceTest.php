<?php

use App\Services\DateTimeService;
use Tests\UnitTestCase;

class DateTimeServiceTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_get_iso_date_time_string_returns_correct_timezone_offset(): void
    {
        $isoDate = DateTimeService::getISODateTimeString('09-09-2025', '12', '30', 'UTC');
        $this->assertEquals('2025-09-09T12:30:00+00:00', $isoDate);

        $timezones = ['UTC', 'Europe/Madrid', 'Asia/Tokyo', 'America/Buenos_Aires', 'America/New_York', 'Africa/Johannesburg', 'Australia/Melbourne'];
        foreach ($timezones as $timezone) {
            $offset = (new DateTimeZone($timezone))->getOffset(new DateTime('2025-09-09T12:30:00+00:00'));
            $isoDate = DateTimeService::getISODateTimeString('09-09-2025', '12', '30', $timezone);
            $signCharacter = '-';
            if ($offset >= 0) {
                $signCharacter = '+';
            }
            $this->assertEquals('2025-09-09T12:30:00'.$signCharacter.sprintf('%02d', abs($offset / 3600)).':00', $isoDate);
        }
    }

    public function test_validate_time_when_valid_time_returns_true(): void
    {
        $this->assertTrue(DateTimeService::validateTime('14:30'));
    }

    public function test_validate_time_when_invalid_time_returns_false(): void
    {
        $this->assertFalse(DateTimeService::validateTime('25:00'));
        $this->assertFalse(DateTimeService::validateTime('14:60'));
        $this->assertFalse(DateTimeService::validateTime('invalid-time'));
        $this->assertFalse(DateTimeService::validateTime('NOTSET'));
        $this->assertFalse(DateTimeService::validateTime(''));
    }
}
