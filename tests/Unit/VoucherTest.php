<?php

namespace Tests\Unit;

use App\Models\DeliveryOptions;
use App\Models\Voucher;
use App\Services\ProductService;
use SimpleXMLElement;
use Tests\UnitTestCase;

class VoucherTest extends UnitTestCase
{
    public const FAKE_AGENT_REF = 'FAKE_AGENT_REF';

    public const FAKE_UTC_REDEEMED_AT = 123456789;

    public SimpleXMLElement $showBookingXML;

    protected function setUp(): void
    {
        parent::setUp();
        $this->showBookingXML = simplexml_load_file('./tests/TourCMSResponses/showBooking.xml')->booking;
    }

    public function test_when_barcode_priority_is_agent_ref_and_its_present_then_we_get_agent_ref()
    {
        $this->showBookingXML->barcode_priority = Voucher::BARCODE_PRIORITY_AGENT_REF;
        $this->showBookingXML->agent_ref = self::FAKE_AGENT_REF;
        $voucher = Voucher::create($this->showBookingXML, ProductService::REDEMPTION_METHOD_DIGITAL, self::FAKE_UTC_REDEEMED_AT);

        $expectedDeliveryOptions = new DeliveryOptions;
        $expectedDeliveryOptions->setDeliveryFormat(ProductService::DELIVERY_FORMAT_QRCODE);
        $expectedDeliveryOptions->setDeliveryValue(self::FAKE_AGENT_REF);

        $this->assertEquals(ProductService::REDEMPTION_METHOD_DIGITAL, $voucher->getRedemptionMethod());
        $this->assertEquals(self::FAKE_UTC_REDEEMED_AT, $voucher->getUtcRedeemedAt());
        $this->assertEquals([$expectedDeliveryOptions], $voucher->getDeliveryOptions());
    }

    public function test_when_barcode_priority_is_agent_ref_and_its_not_present_then_we_get_tourcms_barcodes(): void
    {
        $this->showBookingXML->barcode_priority = Voucher::BARCODE_PRIORITY_AGENT_REF;
        $this->showBookingXML->agent_ref = '';
        $voucher = Voucher::create($this->showBookingXML, ProductService::REDEMPTION_METHOD_DIGITAL, self::FAKE_UTC_REDEEMED_AT);

        $expectedDeliveryOptions = new DeliveryOptions;
        $expectedDeliveryOptions->setDeliveryFormat(ProductService::DELIVERY_FORMAT_QRCODE);
        $expectedDeliveryOptions->setDeliveryValue((string) $this->showBookingXML->barcode_data);

        $this->assertEquals(ProductService::REDEMPTION_METHOD_DIGITAL, $voucher->getRedemptionMethod());
        $this->assertEquals(self::FAKE_UTC_REDEEMED_AT, $voucher->getUtcRedeemedAt());
        $this->assertEquals([$expectedDeliveryOptions], $voucher->getDeliveryOptions());
    }

    public function test_when_barcode_priority_is_tourcms_barcodes_then_we_get_tourcms_barcodes(): void
    {
        $this->showBookingXML->barcode_priority = Voucher::BARCODE_PRIORITY_TOURCMS;
        $voucher = Voucher::create($this->showBookingXML, ProductService::REDEMPTION_METHOD_DIGITAL, self::FAKE_UTC_REDEEMED_AT);

        $expectedDeliveryOptions = new DeliveryOptions;
        $expectedDeliveryOptions->setDeliveryFormat(ProductService::DELIVERY_FORMAT_QRCODE);
        $expectedDeliveryOptions->setDeliveryValue((string) $this->showBookingXML->barcode_data);

        $this->assertEquals(ProductService::REDEMPTION_METHOD_DIGITAL, $voucher->getRedemptionMethod());
        $this->assertEquals(self::FAKE_UTC_REDEEMED_AT, $voucher->getUtcRedeemedAt());
        $this->assertEquals([$expectedDeliveryOptions], $voucher->getDeliveryOptions());
    }
}
