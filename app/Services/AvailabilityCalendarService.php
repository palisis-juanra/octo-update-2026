<?php

namespace App\Services;

use App\Models\Availability\AvailabilityRequest;
use App\Transformers\AvailabilityTransformer;
use App\Transformers\BaseTransformer;

class AvailabilityCalendarService
{
    public TourCMSService $tourCMSService;
    public AvailabilityTransformer $transformer;

    public function __construct(
        TourCMSService $tourCMSService,)
    {
        $this->tourCMSService = $tourCMSService;
        $this->transformer = new AvailabilityTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function getCalendar(AvailabilityRequest $availabilityRequest): array
    {
        $calendar = $availabilityRequest->getCalendarAvailabilities($this->tourCMSService);
        return $calendar;
    }

    public function getAvailabilityCalendarTransformed(array $availabilities): array
    {
        $availabilitiesData = [];
        foreach ($availabilities as $availability) {
            $availabilitiesData[] = $this->transformer->calendarTransform($availability);
        }

        return $availabilitiesData;
    }

}