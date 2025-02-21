<?php

namespace App\Transformers;

use App\Facades\OctoRequestFacade;
use App\Http\Requests\OctoRequest;
use App\Http\Responses\OctoResponse;

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
        $data = [
            'id' => $supplier->getId(),
            'name' => $supplier->getName(),
            "endpoint" => $supplier->getEndpoint(),
            "contact" => [
                "website" => $supplier->getWebsite(),
                "email" => $supplier->getEmail(),
                "telephone" => $supplier->getTelephone(),
                "address" => $supplier->getFullAddress()
            ]
        ];
        if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_CONTENT)) {
            $data['shortDescription'] = $supplier->getShortDescription();
            $data['media'] = [];
            foreach ($supplier->getMedia() as $media) {
                $data['media'][] = $media->toArray();
            }
        }
        return $data;
    }
}
