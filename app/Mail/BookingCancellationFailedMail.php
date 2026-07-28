<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingCancellationFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $oldBooking,
        public Booking $newBooking,
        public string $reason
    ) {}

    public function build(): self
    {
        return $this
            ->subject("Booking update: failed to cancel old booking {$this->oldBooking->getUuid()}")
            ->view('emails.booking-cancellation-failed')
            ->with([
                'oldBookingUuid' => $this->oldBooking->getUuid(),
                'oldBookingId' => $this->oldBooking->getId(),
                'newBookingUuid' => $this->newBooking->getUuid(),
                'newBookingId' => $this->newBooking->getId(),
                'reason' => $this->reason,
            ]);
    }
}
