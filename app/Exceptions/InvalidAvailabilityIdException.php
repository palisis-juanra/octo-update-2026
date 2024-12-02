<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class InvalidAvailabilityIdException extends Exception
{
    protected string $availabilityId;

    public function __construct($availabilityId, string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->availabilityId = $availabilityId;
    }

    public function getOptionId()
    {
        return $this->availabilityId;
    }
}