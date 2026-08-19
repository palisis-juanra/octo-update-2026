<?php

namespace App\Transformers;

class LocationTransformer extends BaseTransformer
{
    protected PlaceTransformer $placeTransformer;

    public function __construct(string $mode)
    {
        parent::__construct($mode);
        $this->placeTransformer = new PlaceTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    protected function basicTransform($location): array
    {
        return [
            'title' => $location->getTitle(),
            'shortDescription' => $location->getShortDescription(),
            'types' => $location->getTypes(),
            'minutesTo' => $location->getMinutesTo(),
            'minutesAt' => $location->getMinutesAt(),
            'place' => $this->placeTransformer->transform($location->getPlace()),
        ];
    }

    protected function fullTransform($location): array
    {

        return $this->basicTransform($location);
    }
}
