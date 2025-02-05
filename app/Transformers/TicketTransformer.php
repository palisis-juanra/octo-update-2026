<?php

namespace App\Transformers;

class TicketTransformer extends BaseTransformer
{
    public function __construct(string $mode)
    {
        parent::__construct($mode);
    }

    public function basicTransform($ticket): array
    {
        return [
            'redemptionMethod' => $ticket->getRedemptionMethod(),
            'utcRedeemedAt' => $ticket->getUtcRedeemedAt(),
            'deliveryOptions' => !empty($ticket->getDeliveryOptions()) ? [$ticket->getDeliveryOptions()] : []
        ];
    }

    public function fullTransform($ticket): array
    {
        return $this->basicTransform($ticket);
    }
}