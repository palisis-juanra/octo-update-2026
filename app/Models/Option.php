<?php

namespace App\Models;

use App\Exceptions\InvalidUnitIdException;
use App\Services\UnitService;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected string $id;
    protected bool $default;
    protected string $internalName;
    protected ?string $reference;
    protected array $availabilityStartTimes;
    protected string $cancellationCutoff;
    protected int $cancellationCutoffAmount;
    protected string $cancellationCutoffUnit;
    protected array $requiredContactFields;
    protected object $restrictions;
    protected array $units;
    protected ?OptionContent $content = null;

    public static function create(object $optionData): Option
    {
        $option = new Option;
        
        $option->setId((string) $optionData->id);
        $option->setDefault($optionData->default);
        $option->setInternalName($optionData->internalName);
        $option->setReference($optionData->reference);
        $option->setAvailabilityStartTimes($optionData->availabilityLocalStartTimes);
        $option->setCancellationCutoff($optionData->cancellationCutoff);
        $option->setCancellationCutoffAmount($optionData->cancellationCutoffAmount);
        $option->setCancellationCutoffUnit($optionData->cancellationCutoffUnit);
        $option->setRequiredContactFields($optionData->requiredContactFields);
        $option->setRestrictions($optionData->restrictions);
        $option->setUnits($optionData->units);

        return $option;
    }

    public function setId(string $id)
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setDefault(bool $default): self
    {
        $this->default = $default;

        return $this;
    }

    public function getDefault(): ?bool
    {
        return $this->default;
    }

    public function setInternalName(string $internalName): self
    {
        $this->internalName = $internalName;

        return $this;
    }

    public function getInternalName(): ?string
    {
        return $this->internalName;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setAvailabilityStartTimes(array $availabilityLocalStartTimes): self
    {
        $this->availabilityLocalStartTimes = $availabilityLocalStartTimes;

        return $this;
    }

    public function getAvailabilityStartTimes(): ?array
    {
        return $this->availabilityLocalStartTimes;
    }

    public function setCancellationCutoff(string $cancellationCutoff): self
    {
        $this->cancellationCutoff = $cancellationCutoff;

        return $this;
    }

    public function getCancellationCutoff(): ?string
    {
        return $this->cancellationCutoff;
    }

    public function setCancellationCutoffAmount(int $cancellationCutoffAmount): self
    {
        $this->cancellationCutoffAmount = $cancellationCutoffAmount;

        return $this;
    }

    public function getCancellationCutoffAmount(): ?int
    {
        return $this->cancellationCutoffAmount;
    }

    public function setCancellationCutoffUnit(string $cancellationCutoffUnit): self
    {
        $this->cancellationCutoffUnit = $cancellationCutoffUnit;

        return $this;
    }

    public function getCancellationCutoffUnit(): ?string
    {
        return $this->cancellationCutoffUnit;
    }

    public function setRequiredContactFields(array $requiredContactFields): self
    {
        $this->requiredContactFields = $requiredContactFields;

        return $this;
    }

    public function getRequiredContactFields(): ?array
    {
        return $this->requiredContactFields;
    }

    public function setRestrictions(object $restrictions): self
    {
        $this->restrictions = $restrictions;

        return $this;
    }

    public function getRestrictions(): ?object
    {
        return $this->restrictions;
    }

    public function setUnits(array $units): self
    {
        $this->units = $units;

        return $this;
    }

    public function getUnits(): ?array
    {
        return $this->units;
    }

    /**
     * Get unit by it's ID
     * @param string $unitId
     * @throws \App\Exceptions\InvalidUnitIdException
     * @return \App\Models\Unit
     */
    public function getUnitById(string $unitId): Unit
    {
        $rateId = UnitService::getTourCMSRateId($unitId);
        foreach ($this->units as $unit) {
            if ($unit->getRateId() == $rateId) {
                return $unit;
            }
        }
        
        throw new InvalidUnitIdException($unitId);
    }

    /**
     * Get the value of content
     */ 
    public function getContent(): OptionContent|null
    {
        return $this->content;
    }

    /**
     * Set the value of content
     *
     * @return  self
     */ 
    public function setContent(OptionContent $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function addUnit(Unit $unit): self
    {
        $this->units[] = $unit;
        return $this;
    }
}
