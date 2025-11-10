<?php

namespace App\Exceptions;

use Exception;

class BookingAlreadyRedeemedException extends Exception {
    public const MESSAGE = 'Booking cannot be cancelled because was already redeemed';
    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct($message);
    }
}


