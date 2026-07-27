<?php

namespace App\Models;

/**
 * Class that represent each one of Mapping Assistant values
 */
class ProductMapping
{
    protected string $type;
    protected string $label;
    protected string $value;
    protected array $startTimes = [];
    protected string $customLabel;

    public function __construct(string $type, string $label, string $value, array $startTimes = [], string $customLabel = '')
    {
        $this->type = $type;
        $this->value = $value;
        $this->label = $label;
        $this->startTimes = $startTimes;
        $this->customLabel = $customLabel;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getStartTimes(): array
    {
        return $this->startTimes;
    }

    public function getCustomLabel(): string
    {
        return $this->customLabel;
    }
}