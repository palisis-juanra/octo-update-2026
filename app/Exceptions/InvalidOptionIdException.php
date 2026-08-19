<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class InvalidOptionIdException extends Exception
{
    public string $optionId;

    public function __construct($optionId, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->optionId = $optionId;
    }

    public function getOptionId()
    {
        return $this->optionId;
    }
}
