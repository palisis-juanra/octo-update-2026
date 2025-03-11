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
use Exception;
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
        $components = XMLService::getArrayFromXmlNode($booking->getBookingData()->components, 'component');
        try {
            $component = self::getComponentByRateId($components, $unit->getId(), $number);
        } catch (ComponentNotFoundException $e) {
            $component = null;
        }

        $unitItem = new UnitItem();
        $unitItem
            ->setUuid($uuid ?? Uuid::uuid4())
            ->setResellerReference(null)
            ->setSupplierReference($unit->reference)
            ->setUnitId($unit->getId())
            ->setId($unit->getId())
            ->setUnit($unit)
            ->setStatus($booking->getStatus())
            ->setUtcRedeemedAt(!empty($component->redeemed_at_utc_seconds) ? (int) $component->redeemed_at_utc_seconds : null)
            ->setContact($contact);

        // We only have to create ticket if the TICKET is present in product's delivery methods
        if (in_array(ProductService::DELIVERY_METHOD_TICKET, $product->getDeliveryMethods())) {


            $ticket = new Ticket();
            $ticket->setRedemptionMethod($product->getRedemptionMethod());
            


            $ticketValue = self::getTicketValueForUnitItem($components, $unit->getId(), $number);
            if (!empty($ticketValue)) {
                $ticket->setRedemptionMethod($booking->getProduct()->getRedemptionMethod());
                $ticket->setUtcRedeemedAt(!empty($component->redeemed_at_utc_seconds) ? (int) $component->redeemed_at_utc_seconds : null);
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
        try {
            $component = self::getComponentByRateId($tourCMSComponents, $unitId, $number);
        } catch (ComponentNotFoundException $e) {
            return null;
        }
    
        if (empty($component)) {
            return null;
        }
    
        $tickets = $component->tickets;
        if (empty($tickets)) { return null; } 
    
        $tickets = XMLService::getArrayFromXmlNode($tickets, 'ticket');
        return (string) $tickets[$number-1]->value;
    
    }

    protected static function getComponentByRateId(array $tourCMSComponents, string $unitId, string $number): ?SimpleXMLElement
    {
        $tcmsRateId = UnitService::getTourCMSRateId($unitId);
        $rates = array_filter($tourCMSComponents, 
        function(SimpleXMLElement $component) use ($tcmsRateId): bool {
            $rateId = explode('|', (string) $component->rate_breakdown)[0];
            return ($rateId == $tcmsRateId) && ((string) $component->date_type === 'departure'); 
        });

        if (count($rates) !== 1) {
            throw new ComponentNotFoundException("Component not found for unit $unitId, number $number"); 
        }

        // reset array keys
        $rates = reset($rates);

        return $rates[0];
    }

}

class ComponentNotFoundException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}   