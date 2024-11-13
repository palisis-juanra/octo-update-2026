<?php

namespace App\Transformers;

class SupplierTransformer extends BaseTransformer
{
    public function __construct(string $mode = BaseTransformer::BASIC)
    {
        parent::__construct($mode);
    }

    protected function basicTransform($supplier): array
    {
        return [
            'id' => $supplier->getId(),
            'name' => $supplier->getName(),
        ];
    }

    protected function fullTransform($supplier): array
    {
        return [
            'id' => $supplier->getId(),
            'name' => $supplier->getName(),
            "endpoint" => $supplier->getEndpoint(),
            "contact" => [
                "website" => $supplier->getWebsite(),
                "email" => $supplier->getEmail(),
                "telephone" => $supplier->getTelephone(),
                "address" => $supplier->getAddress()
            ]
        ];
    }
}
