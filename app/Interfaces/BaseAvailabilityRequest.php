<?php

namespace App\Interfaces;

use App\Services\TourCMSService;

abstract class BaseAvailabilityRequest
{
    const OCTO_STATUS_AVAILABLE = 'AVAILABLE';
    const OCTO_STATUS_FREESALE = 'FREESALE';
    const OCTO_STATUS_SOLD_OUT = 'SOLD_OUT';
    const OCTO_STATUS_LIMITED = 'LIMITED';
    const OCTO_STATUS_CLOSED = 'CLOSED';

    const TCMS_STATUS_OPEN = 'OPEN';
    const TCMS_STATUS_ASKFIRST = 'ASKFIRST';
    const TCMS_STATUS_CLOSED = 'CLOSED';

    protected int $maxUnits;
    protected string $cutoff;

    /**
     * Get all the availabilities
     * @return array of App\Models\Availability
     */
    abstract public function getAvailabilities(TourCMSService $tourCMSService): array;

    /**
     * Map between TourCMS departure status and Octo availability status
     * @return string
     */
    abstract public function getOctoStatusFromTourCMSStatus(string $tourCMSStatus): string;

    public function setMaxUnits(int $maxUnits): void
    {
        $this->maxUnits = $maxUnits;
    }

    public function getMaxUnits(): int
    {
        return $this->maxUnits;
    }

    public function setCutoff(string $cutoff): void
    {
        $this->cutoff = $cutoff;
    }

    public function getCutoff(): string
    {
        return $this->cutoff;
    }
}