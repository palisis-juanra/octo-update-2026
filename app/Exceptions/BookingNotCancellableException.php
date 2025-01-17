<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class BookingNotCancellableException extends Exception 
{
    const ERROR_MESSAGE = 'Booking is not cancellable';
    public function __construct(string $message = self::ERROR_MESSAGE, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

};