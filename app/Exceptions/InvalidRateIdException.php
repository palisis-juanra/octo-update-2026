<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class InvalidRateIdException extends Exception
{
    protected string $rateId;

    public function __construct(string $rateId, string $message = "", int $code = 0, Throwable|null $previous = null)
    {
        $this->rateId = $rateId;
        parent::__construct($message, $code, $previous);
    }

    public function getRateId(): string
    {
        return $this->rateId;
    }
}