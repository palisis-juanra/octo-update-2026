<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class InvalidBookingUUIDException extends Exception
{
    public string $bookingUuid;

    public function __construct(string $bookingUuid, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->bookingUuid = $bookingUuid;
    }

    public function getBookingUuid(): string
    {
        return $this->bookingUuid;
    }
}
