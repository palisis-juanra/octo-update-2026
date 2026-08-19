<?php

namespace App\Transformers;

class OptionContentTransformer extends BaseTransformer
{
    public function __construct(string $mode)
    {
        parent::__construct($mode);
    }

    protected function basicTransform($optionContent): array
    {

        return [
            'title' => $optionContent->getTitle(),
        ];
    }

    protected function fullTransform($optionContent): array
    {
        return $this->basicTransform($optionContent);
    }
}
