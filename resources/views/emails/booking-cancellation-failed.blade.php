<p>A booking update replaced booking <strong>{{ $oldBookingUuid }}</strong> (ID {{ $oldBookingId }})
with new booking <strong>{{ $newBookingUuid }}</strong> (ID {{ $newBookingId }}), but cancelling the
old booking failed.</p>

<p>Both bookings are currently active in TourCMS. The old booking must be cancelled manually.</p>

<p><strong>Reason:</strong> {{ $reason }}</p>
