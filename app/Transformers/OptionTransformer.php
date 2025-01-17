<?php

namespace App\Transformers;

use League\Fractal\Resource\Collection;

class OptionTransformer extends BaseTransformer
{
    protected UnitTransformer $unitTransformer;

    public function __construct(string $mode)
    {
        parent::__construct($mode);
        $this->unitTransformer = new UnitTransformer(BaseTransformer::FULL_TRANSFORM);
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

        $unitsResource = new Collection($option->getUnits(), $this->unitTransformer);
        $unitsTransformed = $this->manager->createData($unitsResource)->toArray()['data'];

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
            'units' => $unitsTransformed
        ];
    }
}
