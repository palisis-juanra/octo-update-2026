<?php

namespace App\Transformers;

class OpeningHoursTransformer extends BaseTransformer
{
    public function __construct(string $mode)
    {
        parent::__construct($mode);
    }

    protected function basicTransform($openingHours): array
    {
        return [
            'from' => $openingHours->getFrom(),
            'to' => $openingHours->getTo(),
        ];
    }

    protected function fullTransform($openingHours): array
    {
        return [
            'from' => $openingHours->getFrom(),
            'to' => $openingHours->getTo(),
        ];
    }
}
