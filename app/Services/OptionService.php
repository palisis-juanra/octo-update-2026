<?php

namespace App\Services;

use App\Exceptions\InvalidOptionIdException;
use Throwable;
use UnhandledMatchError;

class OptionService
{
    const OPTION_SINGLE = 'SINGLE';
    const OPTION_SINGLE_REGEX = '/^SINGLE$/';
    const OPTION_START_TIME = 'START_TIME';
    const OPTION_START_TIME_REGEX = '/^START_TIME$/';
    const OPTION_DEPARTURE_CODE = 'DEPARTURE_CODE';
    const OPTION_DEPARTURE_CODE_REGEX = '/^(DEPARTURE_CODE\|.*)/';
    const OPTION_SUPPLIER_NOTE = 'SUPPLIER_NOTE';
    const OPTION_SUPPLIER_NOTE_REGEX = '/^(SUPPLIER_NOTE\|.*)/';
    const OPTION_SUPPLIER_NOTE_PLUS_START_TIME = 'SUPPLIER_NOTE_PLUS_START_TIME';
    const OPTION_SUPPLIER_NOTE_PLUS_START_TIME_REGEX = '/^(SUPPLIER_NOTE_PLUS_START_TIME\|.*)/';
    const VALID_OPTIONS = [
        self::OPTION_SINGLE_REGEX,
        self::OPTION_START_TIME_REGEX,
        self::OPTION_DEPARTURE_CODE_REGEX,
        self::OPTION_SUPPLIER_NOTE_REGEX,
        self::OPTION_SUPPLIER_NOTE_PLUS_START_TIME_REGEX
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

    /**
     * Get the api querystring based on the mapping type, used for various tourcms api endpoints
     * @param string $optionId
     * @throws \App\Exceptions\InvalidOptionIdException
     * @return string
     */
    public static function getMappingQueryString(string $optionId): string
    {
        if ($optionId === self::OPTION_SINGLE || $optionId === self::OPTION_START_TIME) {
            return '';
        }

        try {
            $optionSplitted = explode("|", $optionId);
            $mappingType = $optionSplitted[0];
            $mappingValue = $optionSplitted[1];
        } catch (Throwable) {
            throw new InvalidOptionIdException($optionId);
        }

        try {
            $mappingField = match($mappingType) {
                self::OPTION_DEPARTURE_CODE => 'code',
                self::OPTION_SUPPLIER_NOTE, self::OPTION_SUPPLIER_NOTE_PLUS_START_TIME => 'supplier_note',
            };
        } catch (UnhandledMatchError) {
            throw new InvalidOptionIdException($optionId);
        }

        $queryString = "{$mappingField}={$mappingValue}";

        return $queryString;
    }
}