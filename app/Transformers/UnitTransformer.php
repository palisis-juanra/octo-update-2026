<?php

namespace App\Transformers;

use App\Facades\OctoRequestFacade;
use App\Http\Requests\OctoRequest;

class UnitTransformer extends BaseTransformer
{
    protected UnitRestrictionsTransformer $unitRestrictionsTranformer;

    protected PricingTransformer $pricingTransformer;

    public function __construct(string $mode)
    {
        parent::__construct($mode);
        $this->unitRestrictionsTranformer = new UnitRestrictionsTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->pricingTransformer = new PricingTransformer;
    }

    public function basicTransform($unit): array
    {
        $data = [
            'id' => $unit->getId(),
            'internalName' => $unit->getInternalName(),
            'reference' => $unit->getReference(),
            'type' => $unit->getType(),
            'requiredContactFields' => $unit->getRequiredContactFields(),
            'restrictions' => $this->unitRestrictionsTranformer->transform($unit->getRestrictions()),
        ];

        if (OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_CONTENT) === true) {
            $data['title'] = $unit->getTitle();
            $data['shortDescription'] = $unit->getShortDescription();
        }

        if (OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_PRICING) === true) {

            if (! is_null($unit->getPricing())) {
                $data['pricingFrom'] = [
                    $this->pricingTransformer->transform($unit->getPricing()),
                ];
            }

        }

        return $data;
    }

    public function fullTransform($unit): array
    {
        return $this->basicTransform($unit);
    }
}
