<?php

namespace App\Models\Availability;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class AvailabilityPricing extends EloquentModel
{
    use HasFactory;
    public string $originalPrice;
    public string $retailPrice;
    public string $netPrice;
    public string $currency;
    public string $currencyPrecision;
    const CURRENCY_PRECISION = 2;

    public function __construct(
        string $originalPrice,
        string $retailPrice,
        string $netPrice,
        string $currency)
    {
        $this->originalPrice = $originalPrice;
        $this->retailPrice = $retailPrice;
        $this->netPrice = $netPrice;
        $this->currency = $currency;
        $this->currencyPrecision = self::CURRENCY_PRECISION;
    }

    /**
     * Get the value of originalPrice
     */ 
    public function getOriginalPrice(): string
    {
        return $this->originalPrice;
    }

    /**
     * Set the value of originalPrice
     *
     * @return  self
     */ 
    public function setOriginalPrice($originalPrice): static
    {
        $this->originalPrice = $originalPrice;

        return $this;
    }

    /**
     * Get the value of retailPrice
     */ 
    public function getRetailPrice(): string
    {
        return $this->retailPrice;
    }

    /**
     * Set the value of retailPrice
     *
     * @return  self
     */ 
    public function setRetailPrice($retailPrice): static
    {
        $this->retailPrice = $retailPrice;

        return $this;
    }

    /**
     * Get the value of netPrice
     */ 
    public function getNetPrice(): string
    {
        return $this->netPrice;
    }

    /**
     * Set the value of netPrice
     *
     * @return  self
     */ 
    public function setNetPrice($netPrice): static
    {
        $this->netPrice = $netPrice;

        return $this;
    }

    /**
     * Get the value of currencyPrecision
     */ 
    public function getCurrencyPrecision(): string
    {
        return $this->currencyPrecision;
    }

    /**
     * Set the value of currencyPrecision
     *
     * @return  self
     */ 
    public function setCurrencyPrecision($currencyPrecision): static
    {
        $this->currencyPrecision = $currencyPrecision;

        return $this;
    }
}