<?php

namespace App\Interfaces;

use App\Services\TourCMSService;

interface BaseAvailabilityRequest
{
    const OCTO_STATUS_AVAILABLE = 'AVAILABLE';
    const OCTO_STATUS_FREESALE = 'FREESALE';
    const OCTO_STATUS_SOLD_OUT = 'SOLD_OUT';
    const OCTO_STATUS_LIMITED = 'LIMITED';
    const OCTO_STATUS_CLOSED = 'CLOSED';

    const TCMS_STATUS_OPEN = 'OPEN';
    const TCMS_STATUS_ASKFIRST = 'ASKFIRST';
    const TCMS_STATUS_CLOSED = 'CLOSED';

    /**
     * Get all the availabilities
     * @return array of App\Models\Availability
     */
    public function getAvailabilities(TourCMSService $tourCMSService): array;

    /**
     * Map between TourCMS departure status and Octo availability status
     * @return string
     */
    public function getOctoStatusFromTourCMSStatus(string $tourCMSStatus): string;
    public function setMaxUnits(int $maxUnits): void;
    public function getMaxUnits(): int;
    public function setCutoff(string $cutoff): void;
    public function getCutoff(): string;
}