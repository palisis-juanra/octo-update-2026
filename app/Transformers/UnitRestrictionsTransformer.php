<?php

namespace App\Transformers;

class UnitRestrictionsTransformer extends BaseTransformer
{
    public function __construct(string $mode)
    {
        parent::__construct($mode);
    }

    public function basicTransform($unitRestrictions): array
    {
        return [
            'minAge' => $unitRestrictions->getMinAge(),
            'maxAge' => $unitRestrictions->getMaxAge(),
            'idRequired' => $unitRestrictions->getIdRequired(),
            'minQuantity' => $unitRestrictions->getMinQuantity(),
            'maxQuantity' => $unitRestrictions->getMaxQuantity(),
            'paxCount' => $unitRestrictions->getPaxCount(),
            'accompaniedBy' => $unitRestrictions->getAccompaniedBy(),
        ];
    }

    public function fullTransform($unitRestrictions): array
    {
        return $this->basicTransform($unitRestrictions);
    }
}
