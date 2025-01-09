<?php

namespace App\Models;

class Unit extends BaseModel
{
    protected string $id;
    protected string $internalName;
    protected ?string $reference;
    protected string $type;
    protected array $requiredContactFields;
    protected UnitRestrictions $restrictions;
    
    public function __construct()
    {

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
     * Get the value of internalName
     */ 
    public function getInternalName(): string
    {
        return $this->internalName;
    }

    /**
     * Set the value of internalName
     *
     * @return  self
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
     *
     * @return  self
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
     *
     * @return  self
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
     *
     * @return  self
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
     *
     * @return  self
     */ 
    public function setRestrictions(UnitRestrictions $restrictions): self
    {
        $this->restrictions = $restrictions;

        return $this;
    }
}