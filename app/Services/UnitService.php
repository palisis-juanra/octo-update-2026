<?php

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\InvalidUnitIdException;

class UnitService
{
    public const UNIT_ID_REGEX = '/^([A-Z]{2}_\d+_\d+\|r\d+)$/';
    public const ID_FIELD = 'id';
    public const UNIT_ID_FIELD = 'unitId';
    public const UNIT_QUANTITY_FIELD = 'quantity';
    public const ERROR_MESSAGE_INVALID_UNITS = 'Invalid units param, must be an array of unit objects';

    public function validateUnits(array $unitsObjects): bool
    {
        foreach ($unitsObjects as $unitObject) {

            if (!is_array($unitObject) || 
                !array_key_exists(self::ID_FIELD, $unitObject) || 
                !array_key_exists(self::UNIT_QUANTITY_FIELD, $unitObject)) {
                throw new BadRequestException(self::ERROR_MESSAGE_INVALID_UNITS);
            }

            $this->validateIdFormat($unitObject[self::ID_FIELD]);
        }

        return true;
    }

    public function validateUnitItems(array $unitItems)
    {
        foreach ($unitItems as $unitObject) {

            if (!is_array($unitObject) || 
                !array_key_exists(self::UNIT_ID_FIELD, $unitObject)) {
                throw new BadRequestException(self::ERROR_MESSAGE_INVALID_UNITS);
            }

            $this->validateIdFormat($unitObject[self::UNIT_ID_FIELD]);
        }

        return true;
    }

    protected function validateIdFormat(string $unitId): bool
    {   
        if (preg_match(self::UNIT_ID_REGEX, $unitId) == false) {
            throw new InvalidUnitIdException($unitId);
        }

        return true;
    }
}