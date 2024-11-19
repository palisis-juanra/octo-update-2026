<?php

namespace App\Services;

use App\Exceptions\InvalidOptionIdException;

class OptionService
{
    const OPTION_SINGLE_REGEX = '/^SINGLE/';
    const OPTION_START_TIME_REGEX = '/^(START_TIME\|([01][0-9]|2[0-3]):([0-5][0-9])$)/';
    const OPTION_DEPARTURE_CODE_REGEX = '/^(DEPARTURE_CODE\|.*)/';
    const SUPPLIER_NOTE_REGEX = '/^(SUPPLIER_NOTE\|.*)/';
    const SUPPLIER_NOTE_PLUS_START_TIME_REGEX = '/^(SUPPLIER_NOTE_PLUS_START_TIME\|.*)/';
    const VALID_OPTIONS = [
        self::OPTION_SINGLE_REGEX,
        self::OPTION_START_TIME_REGEX,
        self::OPTION_DEPARTURE_CODE_REGEX,
        self::SUPPLIER_NOTE_REGEX,
        self::SUPPLIER_NOTE_PLUS_START_TIME_REGEX
    ];

    /**
     * Summary of validateOptionId
     * @throws InvalidOptionIdException
     * @param mixed $optionId
     * @return true
     */
    public function validateOptionId(string $optionId): true
    {
        foreach (self::VALID_OPTIONS as $optionPattern) {
            if (preg_match($optionPattern, $optionId)) {
                return true;
            }
        }

        throw new InvalidOptionIdException($optionId);
    }

    public static function getMappingQueryString(string $optionId): string
    {
        $optionSplitted = explode("|", $optionId);
        $mappingType = $optionSplitted[0];
        $mappingValue = $optionSplitted[1];

        $mappingField = match($mappingType) {
            "START_TIME" => 'start_time',
            "DEPARTURE_CODE" => 'code',
            "SUPPLIER_NOTE", "SUPPLIER_NOTE_PLUS_START_TIME" => 'supplier_note',
        };

        $queryString = "{$mappingField}={$mappingValue}";

        return $queryString;
    }
}