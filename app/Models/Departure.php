<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departure extends Model
{
    use HasFactory;
    protected string $id;
    protected string $localDateTimeStart;
    protected string $localDateTimeEnd;
    protected bool $allDay;
    protected bool $available;
    protected string $status;
    protected int $vacancies;
    protected int $capacity;
    protected int $maxUnits;
    protected string $utcCutoffAt;
    protected string $openingHoursFrom;
    protected string $openingHoursTo;

    public function __construct(
        string $id, 
        string $localDateTimeStart, 
        string $localDateTimeEnd, 
        bool $allDay, 
        bool $available, 
        string $status, 
        int $vacancies, 
        int $capacity, 
        int $maxUnits, 
        string $utcCutoffAt, 
        string $openingHoursFrom, 
        string $openingHoursTo
    )
    {
        $this->id = $id;
        $this->localDateTimeStart = $localDateTimeStart;
        $this->localDateTimeEnd = $localDateTimeEnd;
        $this->allDay = $allDay;
        $this->available = $available;
        $this->status = $status; 
        $this->vacancies = $vacancies;
        $this->capacity = $capacity;
        $this->maxUnits = $maxUnits;
        $this->utcCutoffAt = $utcCutoffAt;
        $this->openingHoursFrom = $openingHoursFrom; 
        $this->openingHoursTo = $openingHoursTo;
    }

    /**
     * Get the value of id
     */ 
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */ 
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of localDateTimeStart
     */ 
    public function getLocalDateTimeStart(): string
    {
        return $this->localDateTimeStart;
    }

    /**
     * Set the value of localDateTimeStart
     *
     * @return  self
     */ 
    public function setLocalDateTimeStart($localDateTimeStart): self
    {
        $this->localDateTimeStart = $localDateTimeStart;

        return $this;
    }

    /**
     * Get the value of localDateTimeEnd
     */ 
    public function getLocalDateTimeEnd(): string
    {
        return $this->localDateTimeEnd;
    }

    /**
     * Set the value of localDateTimeEnd
     *
     * @return  self
     */ 
    public function setLocalDateTimeEnd($localDateTimeEnd): self
    {
        $this->localDateTimeEnd = $localDateTimeEnd;

        return $this;
    }

    /**
     * Get the value of allDay
     */ 
    public function getAllDay(): bool
    {
        return $this->allDay;
    }

    /**
     * Set the value of allDay
     *
     * @return  self
     */ 
    public function setAllDay($allDay): self
    {
        $this->allDay = $allDay;

        return $this;
    }

    /**
     * Get the value of available
     */ 
    public function getAvailable(): bool
    {
        return $this->available;
    }

    /**
     * Set the value of available
     *
     * @return  self
     */ 
    public function setAvailable($available): self
    {
        $this->available = $available;

        return $this;
    }

    /**
     * Get the value of status
     */ 
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set the value of status
     *
     * @return  self
     */ 
    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the value of vacancies
     */ 
    public function getVacancies(): int
    {
        return $this->vacancies;
    }

    /**
     * Set the value of vacancies
     *
     * @return  self
     */ 
    public function setVacancies($vacancies): self
    {
        $this->vacancies = $vacancies;

        return $this;
    }

    /**
     * Get the value of capacity
     */ 
    public function getCapacity(): int
    {
        return $this->capacity;
    }

    /**
     * Set the value of capacity
     *
     * @return  self
     */ 
    public function setCapacity($capacity): self
    {
        $this->capacity = $capacity;

        return $this;
    }

    /**
     * Get the value of maxUnits
     */ 
    public function getMaxUnits(): int
    {
        return $this->maxUnits;
    }

    /**
     * Set the value of maxUnits
     *
     * @return  self
     */ 
    public function setMaxUnits($maxUnits): self
    {
        $this->maxUnits = $maxUnits;

        return $this;
    }

    /**
     * Get the value of utcCutoffAt
     */ 
    public function getUtcCutoffAt(): string
    {
        return $this->utcCutoffAt;
    }

    /**
     * Set the value of utcCutoffAt
     *
     * @return  self
     */ 
    public function setUtcCutoffAt($utcCutoffAt): self
    {
        $this->utcCutoffAt = $utcCutoffAt;

        return $this;
    }

    /**
     * Get the value of openingHoursFrom
     */ 
    public function getOpeningHoursFrom(): string
    {
        return $this->openingHoursFrom;
    }

    /**
     * Set the value of openingHoursFrom
     *
     * @return  self
     */ 
    public function setOpeningHoursFrom($openingHoursFrom): self
    {
        $this->openingHoursFrom = $openingHoursFrom;

        return $this;
    }

    /**
     * Get the value of openingHoursTo
     */ 
    public function getOpeningHoursTo(): string
    {
        return $this->openingHoursTo;
    }

    /**
     * Set the value of openingHoursTo
     *
     * @return  self
     */ 
    public function setOpeningHoursTo($openingHoursTo): self
    {
        $this->openingHoursTo = $openingHoursTo;

        return $this;
    }
}
