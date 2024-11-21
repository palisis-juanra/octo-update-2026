<?php

namespace App\Services;

use DateTime;

class DateTimeService
{
    public static function getISODateTimeString(string $day = 'now', string $hour = '00', string $minutes = '00'): string
    {
        $dateTime = new DateTime($day);
        $dateTime->setTime($hour, $minutes);
        
        return $dateTime->format(DateTime::ATOM);
    }

    public static function getISODateTimeStringFromTimestamp(string $timestamp): string
    {
        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);

        return $dateTime->format(DateTime::ATOM);
    }

    public static function validateDate(string $date, $format = 'Y-m-d'): bool
    {
        $dateTime = DateTime::createFromFormat($format, $date);

        return $dateTime && strtolower($dateTime->format($format)) === strtolower($date);
    }


}