<?php

namespace App\Models;

class Rate
{
    protected string $id;
    protected int $retailPrice;
    protected int $netPrice;

    public function __construct(string $id, int $retailPrice, int $netPrice)
    {
        $this->id = $id;
        $this->retailPrice = $retailPrice;
        $this->netPrice = $netPrice;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRetailPrice(): int
    {
        return $this->retailPrice;
    }

    public function getNetPrice(): int
    {
        return $this->netPrice;
    }
}