<?php

namespace App\Exceptions;

use Exception;

class InvalidUnitIdException extends Exception
{
    public string $unitId;
    public function __construct(string $unitId)
    {
        $this->unitId = $unitId;
    }
}