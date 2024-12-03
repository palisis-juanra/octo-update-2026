<?php

namespace App\Models\Availability;

use App\Models\Availability\AvailabilityPricing;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Availability extends BaseModel
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'availability';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'departure_id', 
        'local_date_time_start', 
        'local_date_time_end', 
        'all_day', 
        'available',
        'status', 
        'vacancies',
        'capacity',
        'max_units',
        'utc_cutoff_at',
        'opening_hours_from',
        'opening_hours_to'
    ];
    
    protected $guarded = ['currency', 'pricing'];

    public static $snakeAttributes = true;

    public string $currency;
    public ?AvailabilityPricing $pricing = null;
    
    public $timestamps = true;

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
     * Get the value of departure_id
     */ 
    public function getDepartureId(): string
    {
        return $this->departureId;
    }

    /**
     * Set the value of departure_id
     *
     * @return  self
     */ 
    public function setDepartureId($departureId): self
    {
        $this->departureId = $departureId;

        return $this;
    }

    /**
     * Get the value of localDateTimeStart
     */ 
    public function getLocalDateTimeStart(): string
    {
        return $this->local_date_time_start;
    }

    /**
     * Set the value of localDateTimeStart
     *
     * @return  self
     */ 
    public function setLocalDateTimeStart($localDateTimeStart): self
    {
        $this->local_date_time_start = $localDateTimeStart;

        return $this;
    }

    /**
     * Get the value of localDateTimeEnd
     */ 
    public function getLocalDateTimeEnd(): string
    {
        return $this->local_date_time_end;
    }

    /**
     * Set the value of localDateTimeEnd
     *
     * @return  self
     */ 
    public function setLocalDateTimeEnd($localDateTimeEnd): self
    {
        $this->local_date_time_end = $localDateTimeEnd;

        return $this;
    }

    /**
     * Get the value of allDay
     */ 
    public function getAllDay(): bool
    {
        return $this->all_day;
    }

    /**
     * Set the value of allDay
     *
     * @return  self
     */ 
    public function setAllDay($allDay): self
    {
        $this->all_day = $allDay;

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
     * Get the value of maxUnits
     */ 
    public function getMaxUnits(): int
    {
        return $this->max_units;
    }

    /**
     * Set the value of maxUnits
     *
     * @return  self
     */ 
    public function setMaxUnits($maxUnits): self
    {
        $this->max_units = $maxUnits;

        return $this;
    }

    /**
     * Get the value of utcCutoffAt
     */ 
    public function getUtcCutoffAt(): string
    {
        return $this->utc_cutoff_at;
    }

    /**
     * Set the value of utcCutoffAt
     *
     * @return  self
     */ 
    public function setUtcCutoffAt($utcCutoffAt): self
    {
        $this->utc_cutoff_at = $utcCutoffAt;

        return $this;
    }

    /**
     * Get the value of openingHoursFrom
     */ 
    public function getOpeningHoursFrom(): string
    {
        return $this->opening_hours_from;
    }

    /**
     * Set the value of openingHoursFrom
     *
     * @return  self
     */ 
    public function setOpeningHoursFrom($openingHoursFrom): self
    {
        $this->opening_hours_from = $openingHoursFrom;

        return $this;
    }

    /**
     * Get the value of openingHoursTo
     */ 
    public function getOpeningHoursTo(): string
    {
        return $this->opening_hours_to;
    }

    /**
     * Set the value of openingHoursTo
     *
     * @return  self
     */ 
    public function setOpeningHoursTo($openingHoursTo): self
    {
        $this->opening_hours_to = $openingHoursTo;

        return $this;
    }

    /**
     * Get the value of currency
     */ 
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Set the value of currency
     *
     * @return  self
     */ 
    public function setCurrency($currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get the value of pricing
     */ 
    public function getPricing(): ?AvailabilityPricing
    {
        return $this->pricing;
    }

    /**
     * Set the value of pricing
     *
     * @return  self
     */ 
    public function setPricing(?AvailabilityPricing $pricing)
    {
        $this->pricing = $pricing;

        return $this;
    }
}
