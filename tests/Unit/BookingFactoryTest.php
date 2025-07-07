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

    public function setUp(): void
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

    public function test_canCreateBookingObjectFromStartNewBookingResponse(): void
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

    public function test_canCreateBookingObjectFromTemporaryBookingResponse(): void
    {
        $booking = $this->factory->createFromShowBookingXML(
            $this->showTemporaryBookingXML->booking->booking_uuid,
            $this->showTemporaryBookingXML,
            $this->product,
            $this->getOption(),
            $this->getAvailability(),
            $this->getUnitItems(),
        );

        $this->assertEquals(Booking::STATUS_ON_HOLD, $booking->getStatus());
    }

    public function test_canCreateBookingObjectFromQuotationOrProvisionalBookingResponse(): void
    {
        $this->showConfirmedBookingXML->booking->status = Booking::TCMS_STATUS_QUOTATION;
        $quotationBooking = $this->factory->createFromShowBookingXML(
            $this->showConfirmedBookingXML->booking->booking_uuid,
            $this->showConfirmedBookingXML,
            $this->product,
            $this->getOption(),
            $this->getAvailability(),
            $this->getUnitItems()
        );

        $this->showConfirmedBookingXML->booking->status = Booking::TCMS_STATUS_PROVISIONAL;
        $provisionalBooking = $this->factory->createFromShowBookingXML(
            $this->showConfirmedBookingXML->booking->booking_uuid,
            $this->showConfirmedBookingXML,
            $this->product,
            $this->getOption(),
            $this->getAvailability(),
            $this->getUnitItems()
        );

        $this->assertEquals(Booking::STATUS_PENDING, $quotationBooking->getStatus());
        $this->assertEquals(Booking::STATUS_PENDING, $provisionalBooking->getStatus());
    }

    public function test_canCreateBookingObjectFromConfirmedBookingResponse(): void
    {
        $booking = $this->factory->createFromShowBookingXML(
            $this->showConfirmedBookingXML->booking->booking_uuid,
            $this->showConfirmedBookingXML,
            $this->product,
            $this->getOption(),
            $this->getAvailability(),
            $this->getUnitItems()
        );

        $this->assertEquals(Booking::STATUS_CONFIRMED, $booking->getStatus());
    }

    public function test_whenSomeComponentIsRedeemed_thenBookingIsRedeemed(): void
    {
        $components = simplexml_load_file('tests/TourCMSResponses/components.xml');
        $components->component[0]->redeemed_at_utc_seconds = 1739282451;
        $components = XMLService::getArrayFromXmlNode($components, 'component');
        $this->assertTrue(BookingFactory::isBookingRedeemed($components));
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

    protected function getUnitItems(): string
    {
        return '[]';
    }

    protected function getContact(): Contact
    {
        return new Contact;
    }
}