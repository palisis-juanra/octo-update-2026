<?php

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\InvalidUnitIdException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class UnitService
{
    public const UNIT_ID_REGEX = '/^[A-Z]{2}_\d+_\d+\|\d+\|r(10|[1-9])$/';
    public const ID_FIELD = 'id';
    public const UNIT_ID_FIELD = 'unitId';
    public const UNIT_QUANTITY_FIELD = 'quantity';
    public const ERROR_MESSAGE_INVALID_UNITS = 'Invalid units param, must be an array of unit objects';
    public const ERROR_MESSAGE_INVALID_UNIT_ITEMS = 'Invalid or empty unitItems';
    public const PARAM_UNITS = 'units';
    public const SEPARATOR = '|';

    public static function buildUnitId(string $tourDistributionIdentifier, string $channelId, string $rateId): string
    {
        return "{$tourDistributionIdentifier}|{$channelId}|{$rateId}";
    }

    /**
     * Get TourCMS Rate ID from OCTO Unit ID
     * @param string $unitId
     * @throws \App\Exceptions\InvalidUnitIdException
     * @return string
     */
    public static function getTourCMSRateId(string $unitId): string
    {
        $unitIdExploded = explode("|", $unitId);

        if (array_key_exists(1, $unitIdExploded) === false) {
            throw new InvalidUnitIdException($unitId);
        }

        return (string) array_pop($unitIdExploded);
    }

    /**
     * Validate that units have proper format and also belongs to product
     * @param array $units
     * @param string $productId
     * @throws \App\Exceptions\BadRequestException
     * @return bool
     */
    public function validateUnits(array $units, string $productId): bool
    {
        if (!empty($units)) {
            foreach ($units as $unitObject) {
                if (!is_array($unitObject) || !array_key_exists(self::ID_FIELD, $unitObject) || !array_key_exists(self::UNIT_QUANTITY_FIELD, $unitObject)) {
                    throw new BadRequestException(self::ERROR_MESSAGE_INVALID_UNITS);
                }
                $this->validateIdFormat($unitObject[self::ID_FIELD]);
                $this->validateUnitIdBelongsToProduct($unitObject[self::ID_FIELD], $productId);
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
    public function validateUnitItems(array $unitItems, ?string $productId = null): bool
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
            if (!is_null($productId)) {
                $this->validateUnitIdBelongsToProduct($unitObject[self::UNIT_ID_FIELD], $productId);
            }
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

    /**
     * Check that unit belogs to a certain product
     * @param string $unitId
     * @param string $productId
     * @throws \App\Exceptions\InvalidUnitIdException
     * @return void
     */
    protected function validateUnitIdBelongsToProduct(string $unitId, string $productId): void
    {
        $unitExploded = explode('|', $unitId);
        $productIdFromUnit = $unitExploded[0] . '|' . $unitExploded[1];
        if ($productIdFromUnit !== $productId) {
            throw new InvalidUnitIdException($unitId);
        }
    }
}