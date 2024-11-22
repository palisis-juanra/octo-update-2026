<?php

namespace App\Models\Availability;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarAvailability extends Model
{
    use HasFactory;
    protected string $localDate;
    protected bool $available;
    protected string $status;
    protected ?int $vacancies;
    protected ?int $capacity;
    protected array $openingHours;

    /**
     * Get the value of localDate
     */ 
    public function getLocalDate(): string
    {
        return $this->localDate;
    }

    /**
     * Set the value of localDate
     *
     * @return  self
     */ 
    public function setLocalDate($localDate): self
    {
        $this->localDate = $localDate;

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
    public function getVacancies(): ?int
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
    public function getCapacity(): ?int
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
     * Get the value of openingHours
     */ 
    public function getOpeningHours()
    {
        return $this->openingHours;
    }

    /**
     * Set the value of openingHours
     *
     * @return  self
     */ 
    public function setOpeningHours($openingHours)
    {
        $this->openingHours = $openingHours;

        return $this;
    }
}
