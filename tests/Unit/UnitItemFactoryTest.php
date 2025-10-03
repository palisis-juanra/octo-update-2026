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
    protected SimpleXMLElement $bookingWithUrlPerPerson;
    protected SimpleXMLElement $bookingWithOneUrlPerComponentXML;
    protected SimpleXMLElement $bookingWithSameUrlPerPersonInComponent;

    public function setUp(): void
    {
        parent::setUp();
        $this->bookingWithTicketsXML = simplexml_load_file('./tests/TourCMSResponses/showBookingWithTickets.xml')->booking;

        $this->bookingWithUrlPerPerson = simplexml_load_file('./tests/TourCMSResponses/showBookingWithUrlPerPerson.xml')->booking;
        $this->bookingWithOneUrlPerComponentXML = simplexml_load_file('./tests/TourCMSResponses/showBookingWithOneUrlPerComponent.xml')->booking;
        $this->bookingWithSameUrlPerPersonInComponent = simplexml_load_file('./tests/TourCMSResponses/showBookingWithSameUrlPerPersonInComponent.xml')->booking;
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
        $this->assertEquals($ticketValue, $unitItem->getTicket()->getDeliveryOptions()['deliveryValue']);
    }

    public function test_create_whenComponentHaveUrls_thenWeGetUrlLink(): void
    {
        $unitItemFactory = new UnitItemFactory();
        $unitItem = $unitItemFactory->create(
            $this->getFakeBooking($this->bookingWithUrlPerPerson),
            $this->getFakeUnit(),
            1
        );

        $urlLink = (string) $this->bookingWithUrlPerPerson->components->component[0]->urls->url->link;
        $this->assertEquals($urlLink, $unitItem->getTicket()->getDeliveryOptions()['deliveryValue']);
    }

    public function test_create_whenComponentHaveUrlsPerPersonInBooking_thenWeGetTheCorrectUrlForEachPerson(): void
    {
        $showBooking = $this->bookingWithUrlPerPerson;

        $unitItemFactory = new UnitItemFactory();
        
        // r1 - 1
        $unitItemRate1Person1 = $unitItemFactory->create(
            $this->getFakeBooking($showBooking),
            $this->getFakeUnit(),
            1
        );
        // r1 - 2
        $unitItemRate1Person2 = $unitItemFactory->create(
            $this->getFakeBooking($showBooking),
            $this->getFakeUnit(),
            2
        );

        // r2 - 1
        $unitItemRate2Person1 = $unitItemFactory->create(
            $this->getFakeBooking($showBooking),
            $this->getFakeUnit(2),
            1
        );

        $urlLink = (string) $showBooking->components->component[0]->urls->url[0]->link;
        $this->assertEquals($urlLink, $unitItemRate1Person1->getTicket()->getDeliveryOptions()['deliveryValue']);

        $urlLink = (string) $showBooking->components->component[0]->urls->url[1]->link;
        $this->assertEquals($urlLink, $unitItemRate1Person2->getTicket()->getDeliveryOptions()['deliveryValue']);

        $urlLink = (string) $showBooking->components->component[1]->urls->url[0]->link;
        $this->assertEquals($urlLink, $unitItemRate2Person1->getTicket()->getDeliveryOptions()['deliveryValue']);
    }

    public function test_create_whenComponentHaveOneUrlPerComponent_thenWeGetTheSameUrlForEachPersonInComponent(): void
    {
        $showBooking = $this->bookingWithOneUrlPerComponentXML;

        $unitItemFactory = new UnitItemFactory();
        
        // r1 - 1
        $unitItemRate1Person1 = $unitItemFactory->create(
            $this->getFakeBooking($showBooking),
            $this->getFakeUnit(),
            1
        );
        // r1 - 2
        $unitItemRate1Person2 = $unitItemFactory->create(
            $this->getFakeBooking($showBooking),
            $this->getFakeUnit(),
            2
        );

        // r2 - 1
        $unitItemRate2Person1 = $unitItemFactory->create(
            $this->getFakeBooking($showBooking),
            $this->getFakeUnit(2),
            1
        );

        $urlLink = (string) $showBooking->components->component[0]->urls->url[0]->link;
        $this->assertEquals($urlLink, $unitItemRate1Person1->getTicket()->getDeliveryOptions()['deliveryValue']);

        $urlLink = (string) $showBooking->components->component[0]->urls->url[0]->link;
        $this->assertEquals($urlLink, $unitItemRate1Person2->getTicket()->getDeliveryOptions()['deliveryValue']);

        $urlLink = (string) $showBooking->components->component[1]->urls->url[0]->link;
        $this->assertEquals($urlLink, $unitItemRate2Person1->getTicket()->getDeliveryOptions()['deliveryValue']);
    }

    // PROTECTED METHODS

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

    protected function getFakeUnit(int $id = 1): Unit
    {
        $unit = new Unit();
        $unit->setId('TE_1_67|142|r' . $id);

        return $unit;
    }

}