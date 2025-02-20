<?php

namespace App\Models;

class ProductPricing
{
    public const PRICING_PER_BOOKING = 'BOOKING';
    public const PRICING_PER_UNIT = 'UNIT';

    protected string $defaultCurrency;
    protected array $availableCurrencies = [];
    protected string $pricingPer;

    public function __construct(string $currency, string $pricingPer = self::PRICING_PER_UNIT)
    {
        $this->defaultCurrency = $currency;
        $this->availableCurrencies[] = $currency;
        $this->pricingPer = $pricingPer;
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