<?php

namespace App\Transformers;

use App\Facades\OctoRequestFacade;

class UnitTransformer extends BaseTransformer
{
    protected UnitRestrictionsTransformer $unitRestrictionsTranformer;
    public function __construct(string $mode)
    {
        parent::__construct($mode);
        $this->unitRestrictionsTranformer = new UnitRestrictionsTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function basicTransform($unit): array
    {
        $data = [
            'id' => $unit->getId(),
            'internalName' => $unit->getInternalName(),
            'reference' => $unit->getReference(),
            'type' => $unit->getType(),
            'requiredContactFields' => $unit->getRequiredContactFields(),
            'restrictions' => $this->unitRestrictionsTranformer->transform($unit->getRestrictions())
        ];

        if (true === OctoRequestFacade::isContentRequired()) {
            $data['title'] = $unit->getTitle();
            $data['shortDescription'] = $unit->getShortDescription();
        }

        return $data;
    }

    public function fullTransform($unit): array
    {
        return $this->basicTransform($unit);
    }
}