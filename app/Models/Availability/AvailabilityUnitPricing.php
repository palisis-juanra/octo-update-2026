<?php

namespace App\Models\Availability;

class AvailabilityUnitPricing
{
    public string $unitId;
    public int $retailPrice;
    public int $netPrice;
    public string $currency;
    public int $currencyPrecision = 2;

    /**
     * Get the value of unitId
     */ 
    public function getUnitId(): string
    {
        return $this->unitId;
    }

    /**
     * Set the value of unitId
     *
     * @return  self
     */ 
    public function setUnitId($unitId): self
    {
        $this->unitId = $unitId;

        return $this;
    }

    /**
     * Get the value of retailPrice
     */ 
    public function getRetailPrice(): int
    {
        return $this->retailPrice;
    }

    /**
     * Set the value of retailPrice
     *
     * @return  self
     */ 
    public function setRetailPrice($retailPrice): self
    {
        $this->retailPrice = $retailPrice;

        return $this;
    }

    /**
     * Get the value of netPrice
     */ 
    public function getNetPrice(): int
    {
        return $this->netPrice;
    }

    /**
     * Set the value of netPrice
     *
     * @return  self
     */ 
    public function setNetPrice($netPrice): self
    {
        $this->netPrice = $netPrice;

        return $this;
    }

    /**
     * Get the value of currency
     */ 
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Set the value of currency
     *
     * @return  self
     */ 
    public function setCurrency($currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get the value of currencyPrecision
     */ 
    public function getCurrencyPrecision(): int
    {
        return $this->currencyPrecision;
    }

    /**
     * Set the value of currencyPrecision
     *
     * @return  self
     */ 
    public function setCurrencyPrecision(int $currencyPrecision): self
    {
        $this->currencyPrecision = $currencyPrecision;

        return $this;
    }
}