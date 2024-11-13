<?php

namespace App\Interfaces;

use App\Services\TourCMSService;

interface AvailabilityRequestInterface
{
    /**
     * Get all the departures
     * @return array of App\Models\Departures
     */
    public function getDepartures(TourCMSService $tourCMSService): array;

    public function getProductId(): string;
}