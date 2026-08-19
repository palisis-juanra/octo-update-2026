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

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingWithTicketsXML = simplexml_load_file('./tests/TourCMSResponses/showBookingWithTickets.xml')->booking;

        $this->bookingWithUrlPerPerson = simplexml_load_file('./tests/TourCMSResponses/showBookingWithUrlPerPerson.xml')->booking;
        $this->bookingWithOneUrlPerComponentXML = simplexml_load_file('./tests/TourCMSResponses/showBookingWithOneUrlPerComponent.xml')->booking;
        $this->bookingWithSameUrlPerPersonInComponent = simplexml_load_file('./tests/TourCMSResponses/showBookingWithSameUrlPerPersonInComponent.xml')->booking;
    }

    public function test_create_when_component_have_ticket_then_we_get_ticket_value(): void
    {
        $unitItemFactory = new UnitItemFactory;
        $unitItem = $unitItemFactory->create(
            $this->getFakeBooking($this->bookingWithTicketsXML),
            $this->getFakeUnit(),
            1
        );

        $ticketValue = (string) $this->bookingWithTicketsXML->components->component[0]->tickets->ticket->value;
        $this->assertEquals($ticketValue, $unitItem->getTicket()->getDeliveryOptions()['deliveryValue']);
    }

    public function test_create_when_component_have_urls_then_we_get_url_link(): void
    {
        $unitItemFactory = new UnitItemFactory;
        $unitItem = $unitItemFactory->create(
            $this->getFakeBooking($this->bookingWithUrlPerPerson),
            $this->getFakeUnit(),
            1
        );

        $urlLink = (string) $this->bookingWithUrlPerPerson->components->component[0]->urls->url->link;
        $this->assertEquals($urlLink, $unitItem->getTicket()->getDeliveryOptions()['deliveryValue']);
    }

    public function test_create_when_component_have_urls_per_person_in_booking_then_we_get_the_correct_url_for_each_person(): void
    {
        $showBooking = $this->bookingWithUrlPerPerson;

        $unitItemFactory = new UnitItemFactory;

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

    public function test_create_when_component_have_one_url_per_component_then_we_get_the_same_url_for_each_person_in_component(): void
    {
        $showBooking = $this->bookingWithOneUrlPerComponentXML;

        $unitItemFactory = new UnitItemFactory;

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

    public function test_create_when_component_does_not_have_url_then_we_default_tourcms_barcode_value(): void
    {
        $showBooking = $this->bookingWithOneUrlPerComponentXML;
        unset($showBooking->components->component[0]->urls->url);
        unset($showBooking->components->component[1]->urls->url);

        $unitItemFactory = new UnitItemFactory;

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

        $this->assertEquals($unitItemRate1Person1->getTicket()->getDeliveryOptions()['deliveryValue'], (string) $this->bookingWithOneUrlPerComponentXML->barcode_data, 'deliveryValue should be default barcode');
        $this->assertEquals($unitItemRate1Person2->getTicket()->getDeliveryOptions()['deliveryValue'], (string) $this->bookingWithOneUrlPerComponentXML->barcode_data, 'deliveryValue should be default barcode');
        $this->assertEquals($unitItemRate2Person1->getTicket()->getDeliveryOptions()['deliveryValue'], (string) $this->bookingWithOneUrlPerComponentXML->barcode_data, 'deliveryValue should be default barcode');
    }

    public function test_create_when_component_have_code128_ticket_then_we_get_ticket_value_and_format()
    {
        $unitItemFactory = new UnitItemFactory;
        $unitItem1 = $unitItemFactory->create(
            $this->getFakeBooking($this->bookingWithTicketsXML),
            $this->getFakeUnit(),
            1
        );

        $unitItem2 = $unitItemFactory->create(
            $this->getFakeBooking($this->bookingWithTicketsXML),
            $this->getFakeUnit(),
            2
        );

        $unitItem3 = $unitItemFactory->create(
            $this->getFakeBooking($this->bookingWithTicketsXML),
            $this->getFakeUnit(2),
            1
        );

        $ticket1Value = (string) $this->bookingWithTicketsXML->components->component[0]->tickets->ticket[0]->value;
        $ticket2Value = (string) $this->bookingWithTicketsXML->components->component[0]->tickets->ticket[1]->value;
        $ticket3Value = (string) $this->bookingWithTicketsXML->components->component[1]->tickets->ticket[0]->value;

        $this->assertEquals($ticket1Value, $unitItem1->getTicket()->getDeliveryOptions()['deliveryValue']);
        $this->assertEquals(ProductService::DELIVERY_FORMAT_CODE128, $unitItem1->getTicket()->getDeliveryOptions()['deliveryFormat']);

        $this->assertEquals($ticket2Value, $unitItem2->getTicket()->getDeliveryOptions()['deliveryValue']);
        $this->assertEquals(ProductService::DELIVERY_FORMAT_CODE128, $unitItem2->getTicket()->getDeliveryOptions()['deliveryFormat']);

        $this->assertEquals($ticket3Value, $unitItem3->getTicket()->getDeliveryOptions()['deliveryValue']);
        $this->assertEquals(ProductService::DELIVERY_FORMAT_CODE128, $unitItem3->getTicket()->getDeliveryOptions()['deliveryFormat']);
    }

    // PROTECTED METHODS

    protected function getFakeBooking(SimpleXMLElement $showBooking, array $deliveryFormats = [ProductService::DELIVERY_FORMAT_PDF_URL]): Booking
    {
        $booking = $this->getMockBuilder(Booking::class)
            ->onlyMethods(['getProduct', 'getBookingData'])
            ->getMock();
        $booking
            ->method('getBookingData')
            ->willReturn($showBooking);
        $booking
            ->method('getProduct')
            ->willReturn($this->getFakeProduct($deliveryFormats));

        return $booking;
    }

    protected function getFakeProduct(array $deliveryFormats = [ProductService::DELIVERY_FORMAT_PDF_URL]): Product
    {
        $product = new Product;
        $product->setDeliveryMethods([ProductService::DELIVERY_METHOD_TICKET]);
        $product->setRedemptionMethod(ProductService::REDEMPTION_METHOD_DIGITAL);
        $product->setDeliveryFormats($deliveryFormats);

        return $product;
    }

    protected function getFakeUnit(int $id = 1): Unit
    {
        $unit = new Unit;
        $unit->setId('TE_1_67|142|r'.$id);

        return $unit;
    }
}
