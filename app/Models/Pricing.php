<?php

namespace App\Models;

class Pricing
{
    public const CURRENCY_PRECISION = 2;

    protected string $currency;
    protected int $currencyPrecision = self::CURRENCY_PRECISION;
    protected int $original;
    protected int $retail;
    protected ?int $net;
    protected array $includedTaxes = [];
    protected ?string $rateId = null;
    /**
     * @var Rate[]
     */
    protected array $rates = [];

    public function __construct(
        int $original,
        int $retail,
        ?int $net,
        string $currency)
    {
        $this->original = $original;
        $this->retail = $retail;
        $this->net = $net;
        $this->currency = $currency;
        $this->currencyPrecision = self::CURRENCY_PRECISION;
    }

    public function getOriginal(): int
    {
        return $this->original;
    }

    public function setOriginal(int $original): static
    {
        $this->original = $original;

        return $this;
    }

    public function getRetail(): int
    {
        return $this->retail;
    }

    public function setRetail(int $retail): static
    {
        $this->retail = $retail;

        return $this;
    }

    public function getNet(): ?int
    {
        return $this->net;
    }

    public function setNet(?int $net): static
    {
        $this->net = $net;

        return $this;
    }
    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrencyPrecision(): int
    {
        return $this->currencyPrecision;
    }

    public function setCurrencyPrecision(int $currencyPrecision): static
    {
        $this->currencyPrecision = $currencyPrecision;

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
     * @return Pricing
     */
    public function setRates(array $rates): static
    {
        $this->rates = $rates;
        return $this;
    }

    public function getRates(): array
    {
        return $this->rates;
    }
}