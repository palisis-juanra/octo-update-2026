<?php

namespace App\Models;

class Ticket
{
    protected string $redemptionMethod;
    protected ?string $utcRedeemedAt = null;
    protected array $deliveryOptions = []; 

    public function __construct()
    {

    }

    /**
     * Get the value of redemptionMethod
     */ 
    public function getRedemptionMethod(): string
    {
        return $this->redemptionMethod;
    }

    /**
     * Set the value of redemptionMethod
     *
     * @return  self
     */ 
    public function setRedemptionMethod($redemptionMethod): self
    {
        $this->redemptionMethod = $redemptionMethod;

        return $this;
    }

    /**
     * Get the value of utcRedeemedAt
     */ 
    public function getUtcRedeemedAt(): string|null
    {
        return $this->utcRedeemedAt;
    }

    /**
     * Set the value of utcRedeemedAt
     *
     * @return  self
     */ 
    public function setUtcRedeemedAt($utcRedeemedAt): self
    {
        $this->utcRedeemedAt = $utcRedeemedAt;

        return $this;
    }

    /**
     * Get the value of deliveryOptions
     */ 
    public function getDeliveryOptions(): array
    {
        return $this->deliveryOptions;
    }

    /**
     * Set the value of deliveryOptions
     *
     * @return  self
     */ 
    public function setDeliveryOptions(array $deliveryOptions): self
    {
        $this->deliveryOptions = $deliveryOptions;

        return $this;
    }
}