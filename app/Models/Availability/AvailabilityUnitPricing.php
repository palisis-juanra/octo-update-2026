<?php

namespace App\Models\Availability;

class AvailabilityUnitPricing
{
    public string $unitId;
    public int $originalPrice;
    public int $retailPrice;
    public int $netPrice;
    public string $currency;
    public int $currencyPrecision = 2;
    public array $includedTaxes = [];

    public function getUnitId(): string
    {
        return $this->unitId;
    }

    public function setUnitId($unitId): self
    {
        $this->unitId = $unitId;

        return $this;
    }

    public function getRetailPrice(): int
    {
        return $this->retailPrice;
    }

    public function setRetailPrice($retailPrice): self
    {
        $this->retailPrice = $retailPrice;

        return $this;
    }

    public function getNetPrice(): int
    {
        return $this->netPrice;
    }

    public function setNetPrice($netPrice): self
    {
        $this->netPrice = $netPrice;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency($currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrencyPrecision(): int
    {
        return $this->currencyPrecision;
    }

    public function setCurrencyPrecision(int $currencyPrecision): self
    {
        $this->currencyPrecision = $currencyPrecision;

        return $this;
    }

    public function getOriginalPrice(): int
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(int $originalPrice): static
    {
        $this->originalPrice = $originalPrice;

        return $this;
    }

    public function getIncludedTaxes(): array
    {
        return $this->includedTaxes;
    }

    public function setIncludedTaxes(array $includedTaxes): static
    {
        $this->includedTaxes = $includedTaxes;

        return $this;
    }
}