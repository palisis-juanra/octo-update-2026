<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class NoAvailabilityException extends Exception
{
    const ERROR_MESSAGE = 'There is no availability for the selected option';

    public function __construct(string $message = self::ERROR_MESSAGE, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
