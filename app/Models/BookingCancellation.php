<?php

namespace App\Models;

class BookingCancellation
{
    public const REFUND_NONE = 'NONE';
    public const REFUND_FULL = 'FULL';

    protected string $refund = self::REFUND_FULL;
    protected ?string $reason;
    protected string $utcCancelledAt;

    public function __construct(string $reason = null, string $utcCancelledAt = null)
    {
        $this->utcCancelledAt = null !== $utcCancelledAt ? $utcCancelledAt : Booking::createUtcCancelledAt(time());
        $this->reason = $reason;
    }

    /**
     * Get the value of refund
     */ 
    public function getRefund(): string
    {
        return $this->refund;
    }

    /**
     * Set the value of refund
     *
     * @return  self
     */ 
    public function setRefund(string $refund): self
    {
        $this->refund = $refund;

        return $this;
    }

    /**
     * Get the value of reason
     */ 
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Set the value of reason
     *
     * @return  self
     */ 
    public function setReason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get the value of utcCancelledAt
     */ 
    public function getUtcCancelledAt(): string
    {
        return $this->utcCancelledAt;
    }

    /**
     * Set the value of utcCancelledAt
     *
     * @return  self
     */ 
    public function setUtcCancelledAt(string $utcCancelledAt): self
    {
        $this->utcCancelledAt = $utcCancelledAt;

        return $this;
    }
}