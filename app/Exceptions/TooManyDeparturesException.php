<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class TooManyDeparturesException extends Exception
{
    public int $departuresCount;

    protected $message = 'Too many departures found. Please refine your search criteria.';

    protected $code = 429; // HTTP status code for Too Many Requests

    public function __construct(int $departuresCount, $message = null, $code = null, ?Throwable $previous = null)
    {
        $this->departuresCount = $departuresCount;
        if ($message) {
            $this->message = $message;
        }
        if ($code) {
            $this->code = $code;
        }
        parent::__construct($this->message, $this->code, $previous);
    }
}
