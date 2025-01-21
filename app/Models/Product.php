<?php

namespace App\Models;

use App\Exceptions\InvalidOptionIdException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected string $id;
    protected string $internalName;
    protected ?string $reference;
    protected string $locale;
    protected string $timeZone;
    protected bool $allowFreesale;
    protected bool $instantConfirmation;
    protected bool $instantDelivery;
    protected bool $availabilityRequired;
    protected string $availabilityType;
    protected array $deliveryFormats;
    protected array $deliveryMethods;
    protected string $redemptionMethod;
    protected array $options;
    protected array $cutoff;
    protected ?ProductContent $content = null;
    protected bool $allDay = false;

    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */ 
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of internalName
     */ 
    public function getInternalName()
    {
        return $this->internalName;
    }

    /**
     * Set the value of internalName
     *
     * @return  self
     */ 
    public function setInternalName($internalName)
    {
        $this->internalName = $internalName;

        return $this;
    }

    /**
     * Get the value of reference
     */ 
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set the value of reference
     *
     * @return  self
     */ 
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get the value of locale
     */ 
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the value of locale
     *
     * @return  self
     */ 
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get the value of timeZone
     */ 
    public function getTimeZone()
    {
        return $this->timeZone;
    }

    /**
     * Set the value of timeZone
     *
     * @return  self
     */ 
    public function setTimeZone($timeZone)
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    /**
     * Get the value of allowFreesale
     */ 
    public function getAllowFreesale()
    {
        return $this->allowFreesale;
    }

    /**
     * Set the value of allowFreesale
     *
     * @return  self
     */ 
    public function setAllowFreesale($allowFreesale)
    {
        $this->allowFreesale = $allowFreesale;

        return $this;
    }

    /**
     * Get the value of instantConfirmation
     */ 
    public function getInstantConfirmation()
    {
        return $this->instantConfirmation;
    }

    /**
     * Set the value of instantConfirmation
     *
     * @return  self
     */ 
    public function setInstantConfirmation($instantConfirmation)
    {
        $this->instantConfirmation = $instantConfirmation;

        return $this;
    }

    /**
     * Get the value of instantDelivery
     */ 
    public function getInstantDelivery()
    {
        return $this->instantDelivery;
    }

    /**
     * Set the value of instantDelivery
     *
     * @return  self
     */ 
    public function setInstantDelivery($instantDelivery)
    {
        $this->instantDelivery = $instantDelivery;

        return $this;
    }

    /**
     * Get the value of availabilityRequired
     */ 
    public function getAvailabilityRequired()
    {
        return $this->availabilityRequired;
    }

    /**
     * Set the value of availabilityRequired
     *
     * @return  self
     */ 
    public function setAvailabilityRequired($availabilityRequired)
    {
        $this->availabilityRequired = $availabilityRequired;

        return $this;
    }

    /**
     * Get the value of deliveryFormats
     */ 
    public function getDeliveryFormats()
    {
        return $this->deliveryFormats;
    }

    /**
     * Set the value of deliveryFormats
     *
     * @return  self
     */ 
    public function setDeliveryFormats($deliveryFormats)
    {
        $this->deliveryFormats = $deliveryFormats;

        return $this;
    }

    /**
     * Get the value of deliveryMethods
     */ 
    public function getDeliveryMethods()
    {
        return $this->deliveryMethods;
    }

    /**
     * Set the value of deliveryMethods
     *
     * @return  self
     */ 
    public function setDeliveryMethods($deliveryMethods)
    {
        $this->deliveryMethods = $deliveryMethods;

        return $this;
    }

    /**
     * Get the value of redemptionMethod
     */ 
    public function getRedemptionMethod()
    {
        return $this->redemptionMethod;
    }

    /**
     * Set the value of redemptionMethod
     *
     * @return  self
     */ 
    public function setRedemptionMethod($redemptionMethod)
    {
        $this->redemptionMethod = $redemptionMethod;

        return $this;
    }

    /**
     * Get the value of options
     */ 
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the value of options
     *
     * @return  self
     */ 
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get the value of availabilityType
     */ 
    public function getAvailabilityType()
    {
        return $this->availabilityType;
    }

    /**
     * Set the value of availabilityType
     *
     * @return  self
     */ 
    public function setAvailabilityType($availabilityType)
    {
        $this->availabilityType = $availabilityType;

        return $this;
    }

    /**
     * Get the value of utcCutoff
     */ 
    public function getUtcCutoff(): string
    {
        return $this->utcCutoff;
    }

    /**
     * Set the value of utcCutoff
     *
     * @return  self
     */ 
    public function setUtcCutoff($utcCutoff): static
    {
        $this->utcCutoff = $utcCutoff;

        return $this;
    }

    public function getMinBookingSize(): int
    {
        return $this->minBookingSize;
    }

    public function setMinBookingSize(int $minBookingSize): self
    {
        $this->minBookingSize = $minBookingSize;

        return $this;
    }

    public function getMaxBookingSize(): int
    {
        return $this->maxBookingSize;
    }

    public function setMaxBookingSize(int $maxBookingSize): self
    {
        $this->maxBookingSize = $maxBookingSize;

        return $this;
    }


    /**
     * Get the value of cutoff
     */ 
    public function getCutoff()
    {
        return $this->cutoff;
    }

    /**
     * Set the value of cutoff
     *
     * @return  self
     */ 
    public function setCutoff(array $cutoff)
    {
        $this->cutoff = $cutoff;

        return $this;
    }

    /**
     * @throws InvalidOptionIdException
     */
    public function getOptionById(string $optionId)
    {
        foreach ($this->getOptions() as $option) {
            if ($option->getId() == $optionId) {
                return Option::create($option);
            }
        }

        throw new InvalidOptionIdException($optionId);
    }

    /**
     * Get the value of content
     */ 
    public function getContent(): ?ProductContent
    {
        return $this->content;
    }

    /**
     * Set the value of content
     *
     * @return  self
     */ 
    public function setContent(?ProductContent $content): self
    {
        $this->content = $content;

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
    public function setAllDay(bool $allDay): self
    {
        $this->allDay = $allDay;

        return $this;
    }
}
