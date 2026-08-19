<?php

namespace App\Models;

class Unit extends BaseModel
{
    public const ADULT = 'ADULT';

    public const YOUTH = 'YOUTH';

    public const CHILD = 'CHILD';

    public const INFANT = 'INFANT';

    public const FAMILY = 'FAMILY';

    public const SENIOR = 'SENIOR';

    public const STUDENT = 'STUDENT';

    public const MILITARY = 'MILITARY';

    public const OTHER = 'OTHER';

    protected string $id;

    protected string $internalName;

    protected ?string $reference;

    protected string $type;

    protected array $requiredContactFields;

    protected UnitRestrictions $restrictions;

    protected string $title;

    protected ?string $shortDescription = null;

    protected string $rateId;

    protected ?Pricing $pricing = null;

    public function __construct() {}

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
     * Get the value of internalName
     */
    public function getInternalName(): string
    {
        return $this->internalName;
    }

    /**
     * Set the value of internalName
     */
    public function setInternalName($internalName): self
    {
        $this->internalName = $internalName;

        return $this;
    }

    /**
     * Get the value of reference
     */
    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * Set the value of reference
     */
    public function setReference($reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get the value of type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the value of type
     */
    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the value of requiredContactFields
     */
    public function getRequiredContactFields(): array
    {
        return $this->requiredContactFields;
    }

    /**
     * Set the value of requiredContactFields
     */
    public function setRequiredContactFields($requiredContactFields): self
    {
        $this->requiredContactFields = $requiredContactFields;

        return $this;
    }

    /**
     * Get the value of restrictions
     */
    public function getRestrictions(): UnitRestrictions
    {
        return $this->restrictions;
    }

    /**
     * Set the value of restrictions
     */
    public function setRestrictions(UnitRestrictions $restrictions): self
    {
        $this->restrictions = $restrictions;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set the value of title
     */
    public function setTitle(string $title): self
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
    public function setShortDescription(string $shortDescription): self
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getRateId(): string
    {
        return $this->rateId;
    }

    public function setRateId($rateId): static
    {
        $this->rateId = $rateId;

        return $this;
    }

    public function getPricing(): ?Pricing
    {
        return $this->pricing;
    }

    public function setPricing(?Pricing $pricing): static
    {
        $this->pricing = $pricing;

        return $this;
    }
}
