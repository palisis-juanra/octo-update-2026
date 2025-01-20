<?php

namespace App\Models;

use SimpleXMLElement;
use stdClass;

class Voucher 
{
    public const BARCODE_PRIORITY_TOURCMS = 'tourcms_barcode';
    public const BARCODE_PRIORITY_AGENT_REF = 'agent_ref';
    public const DELIVERY_FORMAT_QRCODE = 'QRCODE';
    public const DELIVERY_FORMAT_PDF = 'PDF_URL';

    public string $redemptionMethod;
    public ?string $utcRedeemedAt = null;
    public object $deliveryOptions;

    public function __construct()
    {
        $this->deliveryOptions = (object) [
            "deliveryFormat" => self::DELIVERY_FORMAT_QRCODE,
            "deliveryValue" => "" 
        ];
    }


    public static function create(SimpleXMLElement $booking, string $redemptionMethod, ?string $utcRedeemedAt = null): self
    {
        $voucher = new Voucher();
        $voucher->redemptionMethod = $redemptionMethod;
        $voucher->utcRedeemedAt = $utcRedeemedAt;

        switch ((string) $booking->barcode_priority) {
   
            case self::BARCODE_PRIORITY_TOURCMS:
                $voucher->deliveryOptions->deliveryFormat = self::DELIVERY_FORMAT_QRCODE;
                $voucher->deliveryOptions->deliveryValue = (string) $booking->barcode_data;
                break;
    
            case self::BARCODE_PRIORITY_AGENT_REF:
                $voucher->deliveryOptions->deliveryFormat = self::DELIVERY_FORMAT_QRCODE;
                $voucher->deliveryOptions->deliveryValue = !empty($booking->agent_ref) ? (string) $booking->agent_ref  : (string) $booking->barcode_data;
                break;
            
            default:
                $voucher->deliveryOptions->deliveryFormat = self::DELIVERY_FORMAT_QRCODE;
                $voucher->deliveryOptions->deliveryValue = (string) $booking->barcode_data;
                break;
        }

        return $voucher;
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
    public function setRedemptionMethod(string $redemptionMethod): self
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
    public function setUtcRedeemedAt(string $utcRedeemedAt): self
    {
        $this->utcRedeemedAt = $utcRedeemedAt;

        return $this;
    }

    /**
     * Get the value of deliveryOptions
     */ 
    public function getDeliveryOptions(): object
    {
        return $this->deliveryOptions;
    }

    /**
     * Set the value of deliveryOptions
     *
     * @return  self
     */ 
    public function setDeliveryOptions(object $deliveryOptions): self
    {
        $this->deliveryOptions = $deliveryOptions;

        return $this;
    }
}