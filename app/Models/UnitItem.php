<?php

namespace App\Models;

class UnitItem
{
    public string $uuid;
    public string $id;
    public ?string $resellerReference;
    public ?string $supplierReference;
    public string $status;
    public string $unitId;
    public Unit $unit;
    public ?string $utcRedeemedAt;
    public ?Ticket $ticket = null;
    public ?Contact $contact;

    public function __construct()
    {

    }

    /**
     * Get the value of uuid
     */ 
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Set the value of uuid
     *
     * @return  self
     */ 
    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get the value of resellerReference
     */ 
    public function getResellerReference(): ?string
    {
        return $this->resellerReference;
    }

    /**
     * Set the value of resellerReference
     *
     * @return  self
     */ 
    public function setresellerReference(?string $resellerReference): self
    {
        $this->resellerReference = $resellerReference;

        return $this;
    }

    /**
     * Get the value of supplierReference
     */ 
    public function getSupplierReference(): ?string
    {
        return $this->supplierReference;
    }

    /**
     * Set the value of supplierReference
     *
     * @return  self
     */ 
    public function setSupplierReference(?string $supplierReference): self
    {
        $this->supplierReference = $supplierReference;

        return $this;
    }

    /**
     * Get the value of unitId
     */ 
    public function getUnitId(): string
    {
        return $this->unitId;
    }

    /**
     * Set the value of unitId
     *
     * @return  self
     */ 
    public function setUnitId($unitId): self
    {
        $this->unitId = $unitId;

        return $this;
    }

    /**
     * Get the value of unit
     */ 
    public function getUnit(): Unit
    {
        return $this->unit;
    }

    /**
     * Set the value of unit
     *
     * @return  self
     */ 
    public function setUnit($unit): self
    {
        $this->unit = $unit;

        return $this;
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
     * Get the value of utcRedeemedAt
     */ 
    public function getUtcRedeemedAt(): ?string
    {
        return $this->utcRedeemedAt;
    }

    /**
     * Set the value of utcRedeemedAt
     *
     * @return  self
     */ 
    public function setUtcRedeemedAt(?string $utcRedeemedAt): self
    {
        $this->utcRedeemedAt = $utcRedeemedAt;

        return $this;
    }

    /**
     * Get the value of ticket
     */ 
    public function getTicket(): Ticket|null
    {
        return $this->ticket;
    }

    /**
     * Set the value of ticket
     *
     * @return  self
     */ 
    public function setTicket($ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * Get the value of contact
     */ 
    public function getContact(): Contact|null
    {
        return $this->contact;
    }

    /**
     * Set the value of contact
     *
     * @return  self
     */ 
    public function setContact($contact): self
    {
        $this->contact = $contact;

        return $this;
    }
}