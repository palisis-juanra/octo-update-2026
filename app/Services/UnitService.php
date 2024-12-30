<?php

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\InvalidUnitIdException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class UnitService
{
    public const UNIT_ID_REGEX = '/^[A-Z]{2}_\d+_\d+\|r(10|[1-9])$/';
    public const ID_FIELD = 'id';
    public const UNIT_ID_FIELD = 'unitId';
    public const UNIT_QUANTITY_FIELD = 'quantity';
    public const ERROR_MESSAGE_INVALID_UNITS = 'Invalid units param, must be an array of unit objects';
    public const ERROR_MESSAGE_INVALID_UNIT_ITEMS = 'Invalid or empty unitItems';
    public const PARAM_UNITS = 'units';

    public function validateUnits(array $requestParams): bool
    {
        $units = $requestParams[self::PARAM_UNITS] ?? [];
        if (!empty($units)) {
            foreach ($units as $unitObject) {
                if (!is_array($unitObject) || !array_key_exists(self::ID_FIELD, $unitObject) || !array_key_exists(self::UNIT_QUANTITY_FIELD, $unitObject)) {
                    throw new BadRequestException(self::ERROR_MESSAGE_INVALID_UNITS);
                }
                $this->validateIdFormat($unitObject[self::ID_FIELD]);
            }
        }
        return true;
    }

    /**
     * Validate Unit Items
     * @param array $unitItems
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     * @throws \App\Exceptions\BadRequestException
     * @return bool
     */
    public function validateUnitItems(array $unitItems): bool
    {
        if (empty($unitItems)) {
            throw new UnprocessableEntityHttpException(self::ERROR_MESSAGE_INVALID_UNIT_ITEMS);
        }
        
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