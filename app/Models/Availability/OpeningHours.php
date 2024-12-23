<?php

namespace App\Models\Availability;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpeningHours extends Model
{
    use HasFactory;
    public string $from;
    public string $to;
    public function __construct(
        string $from,
        string $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function setFrom($from): self
    {
        $this->from = $from;

        return $this;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function setTo($to): self
    {
        $this->to = $to;

        return $this;
    }
}