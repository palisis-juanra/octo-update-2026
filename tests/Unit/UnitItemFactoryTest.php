<?php

namespace Tests\Unit;

use App\Factories\UnitItemFactory;
use App\Models\Booking;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ProductService;
use SimpleXMLElement;
use Tests\UnitTestCase;

class UnitItemFactoryTest extends UnitTestCase
{
    protected SimpleXMLElement $bookingWithTicketsXML;
    protected SimpleXMLElement $bookingWithUrlsXML;

    public function setUp(): void
    {
        parent::setUp();
        $this->bookingWithTicketsXML = simplexml_load_file('./tests/TourCMSResponses/showBookingWithTickets.xml')->booking;
        $this->bookingWithUrlsXML = simplexml_load_file('./tests/TourCMSResponses/showBookingWithUrls.xml')->booking;

    }

    public function test_create_whenComponentHaveTicket_thenWeGetTicketValue(): void
    {
        $unitItemFactory = new UnitItemFactory();
        $unitItem = $unitItemFactory->create(
            $this->getFakeBooking($this->bookingWithTicketsXML),
            $this->getFakeUnit(),
            1
        );

        $ticketValue = (string) $this->bookingWithTicketsXML->components->component[0]->tickets->ticket->value;
        $this->assertEquals($unitItem->getTicket()->getDeliveryOptions()['deliveryValue'], $ticketValue);
    }

    public function test_create_whenComponentHaveUrls_thenWeGetUrlLink(): void
    {
        $unitItemFactory = new UnitItemFactory();
        $unitItem = $unitItemFactory->create(
            $this->getFakeBooking($this->bookingWithUrlsXML),
            $this->getFakeUnit(),
            1
        );

        $urlLink = (string) $this->bookingWithUrlsXML->components->component[0]->urls->url->link;
        $this->assertEquals($unitItem->getTicket()->getDeliveryOptions()['deliveryValue'], $urlLink);
    }

    protected function getFakeBooking(SimpleXMLElement $showBooking): Booking
    {
        $booking = $this->getMockBuilder(Booking::class)
            ->onlyMethods(['getProduct', 'getBookingData'])
            ->getMock();
        $booking
            ->method('getBookingData')
            ->willReturn($showBooking);
        $booking
            ->method('getProduct')
            ->willReturn($this->getFakeProduct());
        
        return $booking;
    }

    protected function getFakeProduct(): Product
    {
        $product = new Product();
        $product->setDeliveryMethods([ProductService::DELIVERY_METHOD_TICKET]);
        $product->setRedemptionMethod(ProductService::REDEMPTION_METHOD_DIGITAL);
        $product->setDeliveryFormats([ProductService::DELIVERY_FORMAT_PDF_URL]);

        return $product;
    }

    protected function getFakeUnit(): Unit
    {
        $unit = new Unit();
        $unit->setId('TE_1_67|142|r1');

        return $unit;
    }

}