<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;

class OctoRequest
{
    public const string PRODUCT_ID = 'productId';
    public const string OPTION_ID = 'optionId';
    public const string AVAILABILITY_ID = 'availabilityId';
    public const string UNIT_ITEMS = 'unitItems';
    public const string UNITS = 'units';
    public const string UUID = 'uuid';
    public const string CONTACT = 'contact';
    public const string RESELLER_REFERENCE = 'resellerReference';
    public const string RATE_ID = 'rateId';

    // Endpoints
    public const string ENDPOINT_SUPPLIER_GET = 'supplier-get';
    public const string ENDPOINT_SUPPLIERS_GET = 'suppliers-get';
    public const string ENDPOINT_PRODUCTS_GET = 'products-get';
    public const string ENDPOINT_PRODUCT_GET = 'product-get';
    public const string ENDPOINT_AVAILABILITY_CHECK = 'Check availability';
    public const string ENDPOINT_AVAILABILITY_CALENDAR = 'availability-calendar';
    public const string ENDPOINT_BOOKINGS_RESERVATION = 'Create temporary booking';
    public const string ENDPOINT_BOOKINGS_CONFIRMATION = 'Commit booking';
    public const string ENDPOINT_BOOKING_GET = 'booking-get';
    public const string ENDPOINT_BOOKINGS_CANCELLATION = 'Cancel booking';
    public const string ENDPOINT_BOOKINGS_EXTEND = 'bookings-extend';
    public const string ENDPOINT_BOOKINGS_GET = 'bookings-get';
    public const string ENDPOINT_BOOKINGS_UPDATE = 'bookings-get';

    // Capabilities
    public const string CAPABILITIES_HEADER = 'Octo-Capabilities';
    public const string CAPABILITIES_PRICING = 'octo/pricing';
    public const string CAPABILITIES_CONTENT = 'octo/content';
    public const string CAPABILITIES_BOOKINGCOM_RATES = 'bookingcom/rates';

    public const array CAPABILITIES_ALLOWED = [

        self::ENDPOINT_SUPPLIER_GET => [
            self::CAPABILITIES_CONTENT
        ],
        
        self::ENDPOINT_SUPPLIERS_GET => [
            self::CAPABILITIES_CONTENT
        ],

        self::ENDPOINT_PRODUCT_GET => [
            self::CAPABILITIES_PRICING,
            self::CAPABILITIES_CONTENT
        ],

        self::ENDPOINT_PRODUCTS_GET => [
            self::CAPABILITIES_PRICING,
            self::CAPABILITIES_CONTENT
        ],

        self::ENDPOINT_AVAILABILITY_CHECK => [
            self::CAPABILITIES_PRICING,
            self::CAPABILITIES_CONTENT,
            self::CAPABILITIES_BOOKINGCOM_RATES
        ]
    ];

    protected string $capabilitiesHeader = '';
    protected array $requestedCapabilities = [];
    protected array $activeCapabilities = [];

    public function __construct(public Request $request)
    {
        $this->capabilitiesHeader = $request->header(self::CAPABILITIES_HEADER) ?? '';
        $this->requestedCapabilities = $this->getCapabilitiesRequestedFromHeaderString();
        $this->activeCapabilities = array_intersect($this->requestedCapabilities, $this->getAllowedCapabilitiesForThisEndpoint());
    }

    public function isCapabilityActive(string $capability): bool
    {
        return in_array($capability, $this->activeCapabilities);
    }

    public function getActiveCapabilities(): array
    {
        return $this->activeCapabilities;
    }

    public function getActiveCapabilitiesAsString(): string
    { 
        return implode(', ', $this->activeCapabilities);
    }

    protected function getAllowedCapabilitiesForThisEndpoint(): array
    {
        $route = $this->request->route();
        if (is_null($route)) {
            return [];
        }

        return self::CAPABILITIES_ALLOWED[$route->getName()] ?? [];
    }

    protected function getCapabilitiesRequestedFromHeaderString(): array
    {
        $capabilitiesArray = explode(',', strtolower($this->capabilitiesHeader));
        return array_map('trim', $capabilitiesArray);
    }

    protected function isCapabilityHeaderPresent(string $header)
    {
        $capabilitiesArray = explode(',', strtolower($this->capabilitiesHeader));
        $capabilitiesArray = array_map('trim', $capabilitiesArray);
        return !empty($this->capabilitiesHeader) && in_array($header, $capabilitiesArray);
    }

    protected function isCapabilityAllowedForRoute(string $capability): bool
    {
        return in_array($capability, self::CAPABILITIES_ALLOWED[$this->request->route()->getName()] ?? []);
    }
}