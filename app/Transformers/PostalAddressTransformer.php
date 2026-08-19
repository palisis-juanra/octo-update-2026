<?php

namespace App\Transformers;

class PostalAddressTransformer extends BaseTransformer
{
    public function __construct(string $mode)
    {
        parent::__construct($mode);
    }

    protected function basicTransform($postalAddress): array
    {
        return [
            'streetAddress' => $postalAddress->getStreetAddress(),
            'addressLocality' => $postalAddress->getAddressLocality(),
            'addressRegion' => $postalAddress->getAddressRegion(),
            'postalCode' => $postalAddress->getPostalCode(),
            'addressCountry' => $postalAddress->getAddressCountry(),
            'postOfficeBoxNumber' => $postalAddress->getPostOfficeBoxNumber(),
        ];
    }

    protected function fullTransform($postalAddress): array
    {

        return $this->basicTransform($postalAddress);
    }
}
