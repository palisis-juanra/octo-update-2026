<?php

namespace App\Models;

use App\Services\ProductService;
use SimpleXMLElement;
use stdClass;

class Voucher 
{
    public const BARCODE_PRIORITY_TOURCMS = 'tourcms_barcode';
    public const BARCODE_PRIORITY_AGENT_REF = 'agent_ref';
    public const DELIVERY_FORMAT_QRCODE = 'QRCODE';
    public const DELIVERY_FORMAT_PDF = 'PDF_URL';

    public string $redemptionMethod = ProductService::REDEMPTION_METHOD_DIGITAL;
    public ?string $utcRedeemedAt = null;
    public array $deliveryOptions = [];

    public static function createWithoutOptions(string $redemptionMethod): self
    {
        $voucher = new Voucher;
        $voucher->setRedemptionMethod($redemptionMethod);

        return $voucher;
    }

    public static function create(SimpleXMLElement $booking, string $redemptionMethod, ?string $utcRedeemedAt = null): self
    {
        $voucher = new Voucher();
        $voucher->redemptionMethod = $redemptionMethod;
        $voucher->utcRedeemedAt = $utcRedeemedAt;

        $deliveryOptions = new DeliveryOptions;

        switch ((string) $booking->barcode_priority) {
   
            case self::BARCODE_PRIORITY_TOURCMS:
                $deliveryOptions->setDeliveryFormat(self::DELIVERY_FORMAT_QRCODE);
                $deliveryOptions->setDeliveryValue((string) $booking->barcode_data);
                break;
    
            case self::BARCODE_PRIORITY_AGENT_REF:
                $deliveryOptions->setDeliveryFormat(self::DELIVERY_FORMAT_QRCODE);
                $deliveryOptions->setDeliveryValue(!empty($booking->agent_ref) ? (string) $booking->agent_ref  : (string) $booking->barcode_data);
                break;
            
            default:
                $deliveryOptions->setDeliveryFormat(self::DELIVERY_FORMAT_QRCODE);
                $deliveryOptions->setDeliveryValue((string) $booking->barcode_data);
                break;
        }

        $voucher->setDeliveryOptions([$deliveryOptions]);

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