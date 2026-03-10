<?php

namespace App\Models\Availability;

use App\Models\Rate;

class AvailabilityUnitPricing
{
    public string $unitId;
    public int $originalPrice;
    public int $retailPrice;
    public int $netPrice;
    public string $currency;
    public int $currencyPrecision = 2;
    public array $includedTaxes = [];
    public ?string $rateId = null;
    /**
    * @var Rate[] 
    **/
    public array $rates = [];

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

    public function setRateId(?string $rateId): static
    {
        $this->rateId = $rateId;
        return $this;
    }

    public function getRateId(): ?string
    {
        return $this->rateId;
    }

    /**
     * @param Rate[] $rates
     * @return AvailabilityUnitPricing
     */
    public function setRates(array $rates): static
    {
        $this->rates = $rates;
        return $this;
    }

    /**
     * @return Rate[]
     */
    public function getRates(): array
    {
        return $this->rates;
    }
}