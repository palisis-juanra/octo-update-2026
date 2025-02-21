<?php

namespace App\Transformers;

use App\Facades\OctoRequestFacade;
use App\Http\Requests\OctoRequest;
use League\Fractal\Resource\Collection;

class OptionTransformer extends BaseTransformer
{
    protected UnitTransformer $unitTransformer;
    protected ProductContentTransformer $productContentTransformer;

    public function __construct(string $mode)
    {
        parent::__construct($mode);
        $this->unitTransformer = new UnitTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->productContentTransformer = new ProductContentTransformer(BaseTransformer::FULL_TRANSFORM);
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

        $data = [
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

        if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_CONTENT)) {
            $contentData = $this->productContentTransformer->transform($option->getContent());
            $data = array_merge($data, $contentData);
        }
    
        return $data;
    }
}
