<?php

namespace App\Transformers;

class PlaceTransformer extends BaseTransformer
{
    protected PostalAddressTransformer $postalAddressTransformer;

    public function __construct(string $mode)
    {
        parent::__construct($mode);
        $this->postalAddressTransformer = new PostalAddressTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    protected function basicTransform($place): array
    {
        return [
            'latitude' => $place->getLatitude(),
            'longitude' => $place->getLongitude(),
            'postalAddress' => $this->postalAddressTransformer->transform($place->getPostalAddress()),
            'identifiers' => $place->getIdentifiers(),
            'sameAs' => $place->getSameAs(),
        ];
    }

    protected function fullTransform($place): array
    {

        return $this->basicTransform($place);
    }
}
