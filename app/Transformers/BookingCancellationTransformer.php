<?php

namespace App\Transformers;

class BookingCancellationTransformer extends BaseTransformer
{
    public function basicTransform($cancellation): array
    {
        return [
            'refund' => $cancellation->getRefund(),
            'reason' => $cancellation->getReason(),
            'utcCancelledAt' => $cancellation->getUtcCancelledAt()
        ];
    }

    public function fullTransform($cancellation): array
    {
        return $this->basicTransform($cancellation);
    }
}