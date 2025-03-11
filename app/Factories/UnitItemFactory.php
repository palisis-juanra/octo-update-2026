<?php

namespace App\Factories;

use App\Models\Booking;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\UnitItem;
use App\Services\ProductService;
use App\Services\UnitService;
use App\Services\XMLService;
use Ramsey\Uuid\Uuid;
use SimpleXMLElement;

class UnitItemFactory
{
    public static function create(
        Booking $booking,
        Unit $unit,
        int $number,
        ?string $uuid = null
    ): UnitItem
    {
        $product = $booking->getProduct();
        $contact = $booking->getContact();

        $unitItem = new UnitItem();
        $unitItem
            ->setUuid($uuid ?? Uuid::uuid4())
            ->setResellerReference(null)
            ->setSupplierReference($unit->reference)
            ->setUnitId($unit->getId())
            ->setId($unit->getId())
            ->setUnit($unit)
            ->setStatus($booking->getStatus())
            ->setUtcRedeemedAt($booking->getUtcRedeemedAt())
            ->setContact($contact);

        // We only have to create ticket if the TICKET is present in product's delivery methods
        if (in_array(ProductService::DELIVERY_METHOD_TICKET, $product->getDeliveryMethods())) {

            $components = XMLService::getArrayFromXmlNode($booking->getBookingData()->components, 'component');

            $ticket = new Ticket();
            $ticket->setRedemptionMethod($product->getRedemptionMethod());
            
            $ticketValue = self::getTicketValueForUnitItem($components, $unit->getId(), $number);
            if (!empty($ticketValue)) {
                $ticket->setRedemptionMethod($booking->getProduct()->getRedemptionMethod());
                $ticket->setUtcRedeemedAt($booking->getUtcRedeemedAt());
                $ticket->setDeliveryOptions([
                    "deliveryFormat" => $booking->getProduct()->getDeliveryFormats()[0],
                    "deliveryValue" => $ticketValue
                ]);
            }
            $unitItem->setTicket($ticket);
        }

        return $unitItem;
    }

    protected static function getTicketValueForUnitItem(array $tourCMSComponents, string $unitId, int $number): ?string
    {
        $tcmsRateId = UnitService::getTourCMSRateId($unitId);
        $rateComponent = array_filter($tourCMSComponents, 
        function(SimpleXMLElement $component) use ($tcmsRateId) {
            $rateId = explode('|', (string) $component->rate_breakdown)[0];
            return ($rateId == $tcmsRateId) && ((string) $component->date_type === 'departure'); 
        });
    
        if (empty($rateComponent)) {
            return null;
        }
    
        $tickets = reset($rateComponent)->tickets;
        if (empty($tickets)) { return null; } 
    
        $tickets = XMLService::getArrayFromXmlNode($tickets, 'ticket');
        return (string) $tickets[$number-1]->value;
    
    }

}