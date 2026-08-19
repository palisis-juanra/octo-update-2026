<?php

namespace App\Factories;

use App\Exceptions\ComponentNotFoundException;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\UnitItem;
use App\Services\ProductService;
use App\Services\UnitService;
use App\Services\XMLService;
use Ramsey\Uuid\Uuid;
use SimpleXMLElement;
use stdClass;

class UnitItemFactory
{
    public const string MIME_TYPE_PDF = 'application/pdf';

    public static function create(
        Booking $booking,
        Unit $unit,
        int $number,
        ?string $uuid = null
    ): UnitItem {
        $product = $booking->getProduct();
        $contact = $booking->getContact();
        $bookingData = $booking->getBookingData();

        $components = XMLService::getArrayFromXmlNode($bookingData->components, 'component');
        try {
            $component = self::getComponentByRateId($components, $unit->getId(), $number);
        } catch (ComponentNotFoundException $e) {
            $component = null;
        }

        $unitItem = new UnitItem;
        $unitItem
            ->setUuid($uuid ?? Uuid::uuid4())
            ->setResellerReference(null)
            ->setSupplierReference(null)
            ->setUnitId($unit->getId())
            ->setId($unit->getId())
            ->setUnit($unit)
            ->setStatus($booking->getStatus())
            ->setUtcRedeemedAt(! empty($component->redeemed_at_utc_seconds) ? (int) $component->redeemed_at_utc_seconds : null)
            ->setContact($contact);

        if (! is_null($component)) {
            $customerId = self::getCustomerIdForUnitItem($component, $number);
            $unitItem->setCustomerId((int) $customerId)
                ->setSupplierReference($component->operator_reference ?? null);

            $customer = null;
            $customers = XMLService::getArrayFromXmlNode($bookingData->customers, 'customer');
            foreach ($customers as $customerXML) {
                if ($customerXML->customer_id == $customerId) {
                    $customer = $customerXML;
                    break;
                }
            }

            if ($customer) {
                $unitItem->setContact(Contact::createFromCustomerXML($customer));
            }

        }

        // We only have to create ticket if the TICKET is present in product's delivery methods
        if (in_array(ProductService::DELIVERY_METHOD_TICKET, $product->getDeliveryMethods())) {

            $ticket = new Ticket;
            $ticket->setRedemptionMethod(redemptionMethod: $product->getRedemptionMethod());

            $ticketFormatAndValue = self::getTicketFormatAndValueForUnitItem($component, $number, $product->getDeliveryFormats());
            if (! empty($ticketFormatAndValue)) {
                $ticket->setRedemptionMethod($booking->getProduct()->getRedemptionMethod());
                $ticket->setUtcRedeemedAt(! empty($component->redeemed_at_utc_seconds) ? (int) $component->redeemed_at_utc_seconds : null);
                $ticket->setDeliveryOptions([
                    'deliveryFormat' => $ticketFormatAndValue->format,
                    'deliveryValue' => $ticketFormatAndValue->value ?? (string) $bookingData->barcode_data,
                ]);
            }
            $unitItem->setTicket($ticket);
        }

        return $unitItem;
    }

    protected static function getTicketFormatAndValueForUnitItem(?SimpleXMLElement $component, int $number, array $deliveryFormats): object
    {
        $ticket = new stdClass;
        $ticket->format = ProductService::DELIVERY_FORMAT_QRCODE;
        $ticket->value = null;

        if (empty($component)) {
            return $ticket;
        }

        $tickets = $component->tickets;
        $urls = $component->urls;

        if (empty($tickets) && ! isset($urls->url)) {
            return $ticket;
        }

        if (! empty($tickets)) {
            $tickets = XMLService::getArrayFromXmlNode($tickets, 'ticket');
            $value = ! empty($tickets[$number - 1]) ? (string) $tickets[$number - 1]->value : null;
            $ticket->format = ProductService::DELIVERY_FORMATS[(string) $component->barcode_symbology];
            $ticket->value = $value;

            return $ticket;
        }

        if (isset($urls->url) && in_array(ProductService::DELIVERY_FORMAT_PDF_URL, $deliveryFormats)) {
            $urls = XMLService::getArrayFromXmlNode($urls, 'url');

            // Try to set the url for the person $number
            $url = $urls[$number - 1] ?? null;

            // If not, try to use the same url for every person in component
            if (empty($url)) {
                $url = $urls[0] ?? null;
            }

            if (empty($url) || empty($url->link) || (string) $url->mime_type !== self::MIME_TYPE_PDF) {
                return $ticket;
            }
            $ticket->format = ProductService::DELIVERY_FORMAT_PDF_URL;
            $ticket->value = (string) $url->link;

            return $ticket;
        }

        return $ticket;
    }

    protected static function getComponentByRateId(array $tourCMSComponents, string $unitId, string $number): ?SimpleXMLElement
    {
        $tcmsRateId = UnitService::getTourCMSRateId($unitId);
        $rates = array_filter($tourCMSComponents,
            function (SimpleXMLElement $component) use ($tcmsRateId): bool {
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

    protected static function getCustomerIdForUnitItem(SimpleXMLElement $component, int $number): ?int
    {
        if (empty($component) || ! isset($component->customers)) {
            return null;
        }

        $customers = XMLService::getArrayFromXmlNode($component->customers, 'customer');
        if (empty($customers)) {
            return null;
        }

        if (! array_key_exists($number - 1, $customers)) {
            return null;
        }

        return (int) $customers[$number - 1]->customer_id ?? null;
    }
}
