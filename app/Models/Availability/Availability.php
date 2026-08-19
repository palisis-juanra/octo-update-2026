<?php

namespace App\Models\Availability;

use App\Models\Pricing;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Availability extends Model
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
        'opening_hours_to',
    ];

    protected $guarded = ['currency', 'pricing'];

    public static $snakeAttributes = true;

    public string $currency;

    public string $date;

    public ?Pricing $pricing = null;

    public $timestamps = true;

    protected bool $contentEnabled = false;

    protected ?string $title = null;

    protected ?string $shortDescription = null;

    protected array $unitPricing = [];

    /* @var string[] */
    protected array $availableRates = [];

    /**
     * Get the value of id
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the value of id
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
        return $this->departure_id;
    }

    /**
     * Set the value of departure_id
     */
    public function setDepartureId($departureId): self
    {
        $this->departure_id = $departureId;

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
     */
    public function setCurrency($currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get the value of pricing
     */
    public function getPricing(): ?Pricing
    {
        return $this->pricing;
    }

    /**
     * Set the value of pricing
     *
     * @return self
     */
    public function setPricing(?Pricing $pricing)
    {
        $this->pricing = $pricing;

        return $this;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of contentEnabled
     */
    public function getContentEnabled(): bool
    {
        return $this->contentEnabled;
    }

    /**
     * Set the value of contentEnabled
     */
    public function setContentEnabled(bool $contentEnabled): self
    {
        $this->contentEnabled = $contentEnabled;

        return $this;
    }

    /**
     * Get the value of title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set the value of title
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of shortDescription
     */
    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    /**
     * Set the value of shortDescription
     */
    public function setShortDescription(?string $shortDescription): self
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    /**
     * Get the value of unitPricing
     *
     * @return AvailabilityUnitPricing[]
     */
    public function getUnitPricing(): array
    {
        return $this->unitPricing;
    }

    /**
     * Set the value of unitPricing
     */
    public function setUnitPricing(array $unitPricing): self
    {
        $this->unitPricing = $unitPricing;

        return $this;
    }

    public function getAvailableRates(): array
    {
        return $this->availableRates;
    }

    public function setAvailableRates(array $availableRates): self
    {
        $this->availableRates = $availableRates;

        return $this;
    }
}
