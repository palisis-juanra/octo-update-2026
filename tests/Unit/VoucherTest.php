<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Voucher;
use App\Services\ProductService;
use SimpleXMLElement;
use Tests\UnitTestCase;

class VoucherTest extends UnitTestCase
{
    public const FAKE_AGENT_REF = 'FAKE_AGENT_REF';
    public const FAKE_UTC_REDEEMED_AT = 123456789;

    public SimpleXMLElement $showBookingXML;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->showBookingXML = simplexml_load_file('./tests/TourCMSResponses/showBooking.xml')->booking;
    }

    public function test_whenBarcodePriorityIsAgentRefAndItsPresent_thenWeGetAgentRef()
    {
        $this->showBookingXML->barcode_priority = Voucher::BARCODE_PRIORITY_AGENT_REF;
        $this->showBookingXML->agent_ref = self::FAKE_AGENT_REF;
        $voucher = Voucher::create($this->showBookingXML, ProductService::REDEMPTION_METHOD_DIGITAL, self::FAKE_UTC_REDEEMED_AT);


        $expectedDeliveryOptions = (object) [
            "deliveryFormat" => ProductService::DELIVERY_FORMAT_QRCODE,
            "deliveryValue" => self::FAKE_AGENT_REF
        ];

        $this->assertEquals(ProductService::REDEMPTION_METHOD_DIGITAL, $voucher->getRedemptionMethod());
        $this->assertEquals(self::FAKE_UTC_REDEEMED_AT, $voucher->getUtcRedeemedAt());
        $this->assertEquals($expectedDeliveryOptions, $voucher->getDeliveryOptions());
    }

    public function test_whenBarcodePriorityIsAgentRefAndItsNotPresent_thenWeGetTourcmsBarcodes(): void
    {
        $this->showBookingXML->barcode_priority = Voucher::BARCODE_PRIORITY_AGENT_REF;
        $this->showBookingXML->agent_ref = '';
        $voucher = Voucher::create($this->showBookingXML, ProductService::REDEMPTION_METHOD_DIGITAL, self::FAKE_UTC_REDEEMED_AT);


        $expectedDeliveryOptions = (object) [
            "deliveryFormat" => ProductService::DELIVERY_FORMAT_QRCODE,
            "deliveryValue" => (string) $this->showBookingXML->barcode_data
        ];

        $this->assertEquals(ProductService::REDEMPTION_METHOD_DIGITAL, $voucher->getRedemptionMethod());
        $this->assertEquals(self::FAKE_UTC_REDEEMED_AT, $voucher->getUtcRedeemedAt());
        $this->assertEquals($expectedDeliveryOptions, $voucher->getDeliveryOptions());
    }

    public function test_whenBarcodePriorityIsTourcmsBarcodes_thenWeGetTourcmsBarcodes(): void
    {
        $this->showBookingXML->barcode_priority = Voucher::BARCODE_PRIORITY_TOURCMS;
        $voucher = Voucher::create($this->showBookingXML, ProductService::REDEMPTION_METHOD_DIGITAL, self::FAKE_UTC_REDEEMED_AT);


        $expectedDeliveryOptions = (object) [
            "deliveryFormat" => ProductService::DELIVERY_FORMAT_QRCODE,
            "deliveryValue" => (string) $this->showBookingXML->barcode_data
        ];

        $this->assertEquals(ProductService::REDEMPTION_METHOD_DIGITAL, $voucher->getRedemptionMethod());
        $this->assertEquals(self::FAKE_UTC_REDEEMED_AT, $voucher->getUtcRedeemedAt());
        $this->assertEquals($expectedDeliveryOptions, $voucher->getDeliveryOptions());
    }
}