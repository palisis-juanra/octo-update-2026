<?php

namespace App\Models;

class ProductPricing
{
    public const PRICING_PER_BOOKING = 'BOOKING';

    protected string $defaultCurrency;
    protected array $availableCurrencies = [];
    protected string $pricingPer = self::PRICING_PER_BOOKING;

    public function __construct(string $currency)
    {
        $this->defaultCurrency = $currency;
        $this->availableCurrencies[] = $currency;
    }

    public function getDefaultCurrency(): string
    {
        return $this->defaultCurrency;
    }

    public function setDefaultCurrency($defaultCurrency): static
    {
        $this->defaultCurrency = $defaultCurrency;

        return $this;
    }

    public function getAvailableCurrencies(): array
    {
        return $this->availableCurrencies;
    }

    public function setAvailableCurrencies($availableCurrencies): static
    {
        $this->availableCurrencies = $availableCurrencies;

        return $this;
    }

    public function getPricingPer(): string
    {
        return $this->pricingPer;
    }

}