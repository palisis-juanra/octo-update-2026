<?php

namespace App\Services;

use App\Models\Product;
use DateInterval;
use DateTime;
use DateTimeZone;
use SimpleXMLElement;

class CutoffService
{
    public const string TCMS_CUTOFF_BEFORE_START_SECONDS = 'before_start_sec';

    public const string TCMS_CUTOFF_DAY_BEFORE_TIME = 'day_before_time';

    public const string TCMS_CUTOFF_SAME_DAY_TIME = 'same_day_time';

    public const string CUTOFF_FORMAT = 'Y-m-d\TH:i:s\Z';

    public const string UNIX_TIMESTAMP_FORMAT = 'U';

    public const string DATE_MODIFIER_ONE_DAY_LESS = '-1 day';

    public const string TIMEZONE_UTC = 'UTC';

    /**
     * Calculate the cutoff date time in UTC for a given product and departure
     */
    public static function calculateCutoffForDeparture(Product $product, SimpleXMLElement $departure): string
    {
        if (! empty($departure->start_time_utcseconds)) {
            $startDate = $date = DateTime::createFromFormat(self::UNIX_TIMESTAMP_FORMAT, $departure->start_time_utcseconds);
        } else {
            // If departure has not time we assume start time is 09:00
            $startDateTime = $departure->start_date.' 09:00';
            $startDate = $date = new DateTime($startDateTime, new DateTimeZone($product->getTimeZone()));
            $startDate->setTimezone(new DateTimeZone('UTC'));
        }

        $cutoffType = $product->getCutoff()['type'] ?? self::TCMS_CUTOFF_BEFORE_START_SECONDS;
        $cutoffValue = $product->getCutoff()['value'] ?? 0;

        if ($cutoffValue == '0') {
            return $startDate->format(self::CUTOFF_FORMAT);
        }

        switch ($cutoffType) {
            case self::TCMS_CUTOFF_BEFORE_START_SECONDS:
                $startDate->sub(new DateInterval('PT'.$cutoffValue.'S'));
                break;

            case self::TCMS_CUTOFF_DAY_BEFORE_TIME:
                $startDate->setTimezone(new DateTimeZone($product->getTimeZone()));
                $date->modify(self::DATE_MODIFIER_ONE_DAY_LESS);

                [$hour, $minutes] = self::getHourAndMinutesFromCutoffValue($cutoffValue);

                $date->setTime($hour, $minutes);
                $date->setTimezone(new DateTimeZone(self::TIMEZONE_UTC));
                break;

            case self::TCMS_CUTOFF_SAME_DAY_TIME:
                $startDate->setTimezone(new DateTimeZone($product->getTimeZone()));

                [$hour, $minutes] = self::getHourAndMinutesFromCutoffValue($cutoffValue);

                $date->setTime($hour, $minutes);
                $date->setTimezone(new DateTimeZone(self::TIMEZONE_UTC));
                break;

        }

        return $startDate->format(self::CUTOFF_FORMAT);
    }

    public static function getHourAndMinutesFromCutoffValue(string $cutoffValue): array
    {
        $valueSplitted = explode(':', $cutoffValue);
        $hour = $valueSplitted[0] ? (int) $valueSplitted[0] : 0;
        $minutes = $valueSplitted[1] ? (int) $valueSplitted[1] : 0;

        return [$hour, $minutes];
    }
}
