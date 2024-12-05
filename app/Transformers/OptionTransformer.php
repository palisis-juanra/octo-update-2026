<?php

namespace App\Transformers;

class OptionTransformer extends BaseTransformer
{

    public function __construct(string $mode)
    {
        parent::__construct($mode);
    }

    protected function basicTransform($option): array
    {
        return [
            'id' => $option->getId(),
            'reference' => $option->getReference(),
        ];
    }

    protected function fullTransform($option): array
    {
        return [
            'id' => $option->getId(),
            'default' => $option->getDefault(),
            'internalName' => $option->getInternalName(),
            'reference' => $option->getReference(),
            'availabilityLocalStartTimes' => $option->getAvailabilityStartTimes(),
            'cancellationCutoff' => $option->getCancellationCutoff(),
            'cancellationCutoffAmount' => $option->getCancellationCutoffAmount(),
            'cancellationCutoffUnit' => $option->getCancellationCutoffUnit(),
            'requiredContactFields' => $option->getRequiredContactFields(),
            'restrictions' => $option->getRestrictions(),
            'units' => $option->getUnits() 
        ];
    }
}
