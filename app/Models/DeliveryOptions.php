<?php

namespace App\Models;

class DeliveryOptions
{
    protected string $deliveryFormat;
    protected string $deliveryValue;

    public function getDeliveryFormat(): string
    {
        return $this->deliveryFormat;
    }

    public function setDeliveryFormat(string $deliveryFormat): self
    {
        $this->deliveryFormat = $deliveryFormat;

        return $this;
    }
    public function getDeliveryValue(): string
    {
        return $this->deliveryValue;
    }

    public function setDeliveryValue(string $deliveryValue): self
    {
        $this->deliveryValue = $deliveryValue;

        return $this;
    }

}