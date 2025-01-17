<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;

class OctoRequest
{
    public const PRODUCT_ID = 'productId';
    public const OPTION_ID = 'optionId';
    public const AVAILABILITY_ID = 'availabilityId';
    public const UNIT_ITEMS = 'unitItems';
    public const UUID = 'uuid';
    public const CONTACT = 'contact';
    public const RESELLER_REFERENCE = 'resellerReference';
    public const CAPABILITIES_HEADER = 'Octo-Capabilities';
    public const CAPABILITIES_PRICING = 'octo/pricing';
    public const CAPABILITIES_CONTENT = 'octo/content';

    protected string $capabilitiesHeader = '';
    protected bool $pricingCapability = false;
    protected bool $contentCapability = false;

    public function __construct(public Request $request)
    {
        $this->capabilitiesHeader = $request->header(self::CAPABILITIES_HEADER) ?? '';
        $this->pricingCapability = $this->isCapabilityHeaderPresent(self::CAPABILITIES_PRICING);
        $this->contentCapability = $this->isCapabilityHeaderPresent(self::CAPABILITIES_CONTENT);
    }

    public function isPricingRequired(): bool
    {
        return $this->pricingCapability;
    }

    public function isContentRequired(): bool
    {
        return $this->contentCapability;
    }

    protected function isCapabilityHeaderPresent(string $header)
    {
        $capabilitiesArray = explode(',', strtolower($this->capabilitiesHeader));
        $capabilitiesArray = array_map('trim', $capabilitiesArray);
        return !empty($this->capabilitiesHeader) && in_array($header, $capabilitiesArray);
    }
}