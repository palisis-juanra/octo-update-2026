<?php

namespace App\Exceptions;

use Exception;

class PromotionNotApplicableException extends Exception
{
    protected string $promotionName = "";

    public function __construct(string $promotionName, string $madeDate)
    {
        parent::__construct("Promotion '{$promotionName}' does not apply to availability date '{$madeDate}'.");
        $this->promotionName = $promotionName;
    }

    public function getPromotionName()
    {
        return $this->promotionName;
    }
}