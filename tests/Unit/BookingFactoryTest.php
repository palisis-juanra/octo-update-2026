<?php

namespace Tests\Unit;

use App\Factories\BookingFactory;
use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Option;
use App\Models\Product;
use App\Services\JSONLogService;
use App\Services\ProductMappingFactory;
use App\Services\ProductService;
use App\Services\TourCMSService;
use App\Services\XMLService;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tests\FeatureTestCase;

class BookingFactoryTest extends FeatureTestCase
{
    public const SUPPLIER_REF = 'thisanoperatorRef';

    public BookingFactory $factory;

    public SimpleXMLElement $showTourXML;

    public SimpleXMLElement $startNewBookingXML;

    public SimpleXMLElement $showTemporaryBookingXML;

    public SimpleXMLElement $showConfirmedBookingXML;

    public MockObject $loggerMock;

    public MockObject $productServiceMock;

    public MockObject $tourCMSServiceMock;

    public Product $product;

    public array $options;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new BookingFactory;

        $this->showTourXML = simplexml_load_file('tests/TourCMSResponses/showTour_67.xml');
        $this->startNewBookingXML = simplexml_load_file('tests/TourCMSResponses/startNewBooking.xml');
        $this->showTemporaryBookingXML = simplexml_load_file('tests/TourCMSResponses/showTemporaryBooking.xml');
        $this->showConfirmedBookingXML = simplexml_load_file('tests/TourCMSResponses/showBooking.xml');

        $this->loggerMock = $this->getMockBuilder(JSONLogService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tourCMSServiceMock->method('showChannel')->willReturn(simplexml_load_file('tests/TourCMSResponses/showChannel.xml'));

        $this->productServiceMock = $this->getMockBuilder(ProductService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductLocale'])
            ->getMock();

        $this->productServiceMock->tourCMSService = $this->tourCMSServiceMock;
        $this->productServiceMock->logger = $this->loggerMock;
        $this->productServiceMock->productMappingFactory = new ProductMappingFactory;

        $this->productServiceMock->method('getProductLocale')->willReturn('en-GB');

        $this->product = $this->productServiceMock->createProductFromTourXML($this->showTourXML->tour);
        $this->options = $this->productServiceMock->getProductOptions($this->showTourXML->tour);

    }

    public function test_can_create_booking_object_from_start_new_booking_response(): void
    {
        $booking = $this->factory->createFromStartNewBookingXML(
            $this->startNewBookingXML,
            $this->product,
            $this->getOption(),
            $this->getAvailability(),
            json_decode($this->getUnitItems(), 1)
        );

        $this->assertEquals(Booking::STATUS_ON_HOLD, $booking->getStatus());
    }

    public function test_can_create_booking_object_from_temporary_booking_response(): void
    {
        $booking = $this->factory->createFromShowBookingXML(
            $this->showTemporaryBookingXML->booking->booking_uuid,
            $this->showTemporaryBookingXML,
            $this->product,
            $this->getAvailability(),
            $this->getUnitItems(),
            $this->getOption()
        );

        $this->assertEquals(Booking::STATUS_ON_HOLD, $booking->getStatus());
    }

    public function test_can_create_booking_object_from_quotation_or_provisional_booking_response(): void
    {
        $this->showConfirmedBookingXML->booking->status = Booking::TCMS_STATUS_QUOTATION;
        $quotationBooking = $this->factory->createFromShowBookingXML(
            $this->showConfirmedBookingXML->booking->booking_uuid,
            $this->showConfirmedBookingXML,
            $this->product,
            $this->getAvailability(),
            $this->getUnitItems(),
            $this->getOption()
        );

        $this->showConfirmedBookingXML->booking->status = Booking::TCMS_STATUS_PROVISIONAL;
        $provisionalBooking = $this->factory->createFromShowBookingXML(
            $this->showConfirmedBookingXML->booking->booking_uuid,
            $this->showConfirmedBookingXML,
            $this->product,
            $this->getAvailability(),
            $this->getUnitItems(),
            $this->getOption()
        );

        $this->assertEquals(Booking::STATUS_PENDING, $quotationBooking->getStatus());
        $this->assertEquals(Booking::STATUS_PENDING, $provisionalBooking->getStatus());
    }

    public function test_can_create_booking_object_from_confirmed_booking_response(): void
    {
        $booking = $this->factory->createFromShowBookingXML(
            $this->showConfirmedBookingXML->booking->booking_uuid,
            $this->showConfirmedBookingXML,
            $this->product,
            $this->getAvailability(),
            $this->getUnitItems(),
            $this->getOption()
        );

        $this->assertEquals(Booking::STATUS_CONFIRMED, $booking->getStatus());
    }

    public function test_when_some_component_is_redeemed_then_booking_is_redeemed(): void
    {
        $components = simplexml_load_file('tests/TourCMSResponses/components.xml');
        $components->component[0]->redeemed_at_utc_seconds = 1739282451;
        $components = XMLService::getArrayFromXmlNode($components, 'component');
        $this->assertTrue(BookingFactory::isBookingRedeemed($components));
    }

    public function test_create_from_show_booking_xm_l_when_operator_reference_is_the_same_across_components_then_booking_has_supplier_reference(): void
    {
        $booking = $this->factory->createFromShowBookingXML(
            $this->showTemporaryBookingXML->booking->booking_uuid,
            $this->showConfirmedBookingXML,
            $this->product,
            $this->getAvailability(),
            $this->getNotEmptyUnitItems(),
            $this->getOption()
        );

        $this->assertEquals(self::SUPPLIER_REF, $booking->getSupplierReference());
    }

    public function test_create_from_show_booking_xm_l_when_operator_reference_is_different_across_components_then_booking_does_not_have_supplier_reference(): void
    {
        $this->showConfirmedBookingXML->booking->components->component[0]->operator_reference = 'something';
        $this->showConfirmedBookingXML->booking->components->component[1]->operator_reference = 'somethingElse';

        $booking = $this->factory->createFromShowBookingXML(
            $this->showTemporaryBookingXML->booking->booking_uuid,
            $this->showConfirmedBookingXML,
            $this->product,
            $this->getAvailability(),
            $this->getNotEmptyUnitItems(),
            $this->getOption()
        );

        $this->assertEquals(null, $booking->getSupplierReference());
    }

    protected function getOption(): Option
    {
        return $this->options[0];
    }

    protected function getAvailability(): Availability
    {
        return (new Availability)
            ->setId('FAKE_AVAILABILITY_ID');
    }

    protected function getNotEmptyUnitItems(): string
    {
        return '[{"id": "TE_1_67|142|r1","unit": [],"uuid": "369d1c8c-e9fd-47b5-8969-7d066fd44f41","status": "CONFIRMED","ticket": null,"unitId": "TE_1_67|142|r1","contact": [],"customerId": 12571,"utcRedeemedAt": null,"resellerReference": null,"supplierReference": "thisanoperatorRef"},{"id": "TE_1_67|142|r2","unit": [],"uuid": "228a6d6c-cb91-4ba6-a55d-d175805fa7c9","status": "CONFIRMED","ticket": null,"unitId": "TE_1_67|142|r2","contact": [],"customerId": 12572,"utcRedeemedAt": null,"resellerReference": null,"supplierReference": "thisanoperatorRef"}]';
    }

    protected function getUnitItems(): string
    {
        return '[]';
    }

    protected function getContact(): Contact
    {
        return new Contact;
    }
}
