<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class InvalidProductIdException extends Exception
{
    public string $productId;
    public function __construct($productId, string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->productId = $productId;
    }
};