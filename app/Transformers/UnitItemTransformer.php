<?php

namespace App\Transformers;

class UnitItemTransformer extends BaseTransformer
{
    protected UnitTransformer $unitTransformer;
    protected TicketTransformer $ticketTransformer;
    protected ContactTransformer $contactTransformer;

    public function __construct(string $mode)
    {
        parent::__construct($mode);
        $this->unitTransformer = new UnitTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->ticketTransformer = new TicketTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->contactTransformer = new ContactTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function basicTransform($unitItem): array
    {
        return [
            'uuid' => $unitItem->getUuid(),
            'resellerReference' => $unitItem->getResellerReference(),
            'supplierReference' => $unitItem->getSupplierReference(),
            'unitId' => $unitItem->getUnitId(),
            'unit' => $this->unitTransformer->transform($unitItem->getUnit()),
            'status' => $unitItem->getStatus(),
            'utcRedeemedAt' => $unitItem->getUtcRedeemedAt(),
            'contact' => $this->contactTransformer->transform($unitItem->getContact()),
            'ticket' => ($unitItem->getTicket() !== null) ? $this->ticketTransformer->transform($unitItem->getTicket()) : null
        ];
    }

    public function fullTransform($unitItem): array
    {
        return $this->basicTransform($unitItem);
    }
}