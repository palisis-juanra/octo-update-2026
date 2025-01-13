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
    protected array $availabilityIds;
    protected bool $contentEnabled = false;
    protected ?string $tourName = null;

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

    /**
     * Get the value of availabilityIds
     */ 
    public function getAvailabilityIds(): array
    {
        return $this->availabilityIds;
    }

    /**
     * Set the value of availabilityIds
     *
     * @return  self
     */ 
    public function setAvailabilityIds($availabilityIds): self
    {
        $this->availabilityIds = $availabilityIds;

        return $this;
    }

    public function getContentEnabled(): bool
    {
        return $this->contentEnabled;
    }

    public function setContentEnabled(bool $contentEnabled): self
    {
        $this->contentEnabled = $contentEnabled;

        return $this;
    }

    /**
     * Get the value of tourName
     */ 
    public function getTourName(): ?string
    {
        return $this->tourName;
    }

    /**
     * Set the value of tourName
     *
     * @return  self
     */ 
    public function setTourName(?string $tourName): self
    {
        $this->tourName = $tourName;

        return $this;
    }
}