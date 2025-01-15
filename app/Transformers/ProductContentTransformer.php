<?php

namespace App\Transformers;

use League\Fractal\Resource\Collection;

class ProductContentTransformer extends BaseTransformer
{
    protected LocationTransformer $locationTransformer;
 
    public function __construct(string $mode)
    {
        parent::__construct($mode);
        $this->locationTransformer = new LocationTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    protected function basicTransform($productContent): array
    {
        $locationsResource = new Collection($productContent->getLocations(), $this->locationTransformer);
        $locationsData = $this->manager->createData($locationsResource)->toArray()['data'];

        return [
            'title' => $productContent->getTitle(),
            'shortDescription' => $productContent->getShortDescription(),
            'description' => $productContent->getDescription(),
            'features' => $productContent->getFeatures(),
            'faqs' => $productContent->getFaqs(),
            'media' => $productContent->getMedia(),
            'locations' => $locationsData,
            'categoryLabels' => $productContent->getCategoryLabels(),
            'durationMinutesFrom' => $productContent->getDurationMinutesFrom(),
            'durationMinutesTo' => $productContent->getDurationMinutesTo(),
            'commentary' => $productContent->getCommentary()
        ];
    }

    protected function fullTransform($productContent): array
    {
        return $this->basicTransform($productContent);
    }
}
