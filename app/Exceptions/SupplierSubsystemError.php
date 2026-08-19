<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class SupplierSubsystemError extends Exception
{
    protected ?string $errorMessage;

    public function __construct(?string $errorMessage, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->errorMessage = $errorMessage;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
