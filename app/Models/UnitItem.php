<?php

namespace App\Models;

use App\Services\DateTimeService;
use DateTime;
use DateTimeZone;

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
    public ?int $customerId = null;

    public function __construct() {}

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getResellerReference(): ?string
    {
        return $this->resellerReference;
    }

    public function setresellerReference(?string $resellerReference): self
    {
        $this->resellerReference = $resellerReference;

        return $this;
    }

    public function getSupplierReference(): ?string
    {
        return $this->supplierReference;
    }

    public function setSupplierReference(?string $supplierReference): self
    {
        $this->supplierReference = $supplierReference;

        return $this;
    }

    public function getUnitId(): string
    {
        return $this->unitId;
    }

    public function setUnitId($unitId): self
    {
        $this->unitId = $unitId;

        return $this;
    }

    public function getUnit(): Unit
    {
        return $this->unit;
    }

    public function setUnit($unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getUtcRedeemedAt(): ?string
    {
        return $this->utcRedeemedAt;
    }
 
    public function setUtcRedeemedAt(?string $timestamp): self
    {

        if (is_null($timestamp)) {
            $this->utcRedeemedAt = null;
            return $this;
        }

        $dateTime = new DateTime('now', new DateTimeZone('UTC'));
        $dateTime->setTimestamp($timestamp);
        $this->utcRedeemedAt = DateTimeService::getISO8601DateFormatted($dateTime);

        return $this;
    }

    public function getTicket(): Ticket|null
    {
        return $this->ticket;
    }

    public function setTicket($ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getContact(): Contact|null
    {
        return $this->contact;
    }

    public function setContact($contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId($customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }
}