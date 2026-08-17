<?php

namespace App\Services;

use App\Exceptions\BookingNotCancellableException;
use App\Exceptions\InvalidBookingUUIDException;
use App\Mail\BookingCancellationFailedMail;
use App\Models\Booking;
use App\Models\BookingCancellation;
use App\Transformers\BaseTransformer;
use App\Transformers\BookingTransformer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use SimpleXMLElement;
use Throwable;

class BookingUpdateService
{
    public const FIELD_OPTION_ID = 'optionId';
    public const FIELD_AVAILABILITY_ID = 'availabilityId';
    public const FIELD_UNIT_ITEMS = 'unitItems';
    public const FIELD_NOTES = 'notes';
    public const FIELD_CONTACT = 'contact';
    public const FIELD_RESELLER_REFERENCE = 'resellerReference';

    public const BOOKING_REPLACED_AUDIT_NOTE_PREFIX = 'Booking updated. Replaced by new booking, TourCMS ID: ';
    public const BOOKING_REBOOK_AUDIT_NOTE_PREFIX = 'This booking is a rebook of previous booking. Old TourCMS ID: ';

    public BookingTransformer $transformer;

    public function __construct(
        public TourCMSService $tourCMSService,
        public BookingCancellationService $bookingCancellationService,
        public BookingReservationService $bookingReservationService,
        public BookingConfirmationService $bookingConfirmationService,
        public BookingContactService $bookingContactService,
        public ProductService $productService,
        public OptionService $optionService,
        public AvailabilityService $availabilityService,
        public UnitService $unitService,
        public JSONLogService $logger,
    ) {
        $this->transformer = new BookingTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    /**
     * Update a booking following the Octo PATCH /bookings/{uuid} semantics:
     * check cancellable, load original JSON, patch changes, create a new booking
     * that keeps the same public uuid, cancel the old one on success (auditing the
     * replacement on both bookings), and notify by email if the old booking's
     * cancellation fails.
     *
     * @return array the transformed new Booking, ready to be returned as the response
     */
    public function update(string $uuid, array $requestParams): array
    {
        $this->logger->info(["message" => "Starting to process booking update request", "uuid" => $uuid, "request" => $requestParams]);

        $oldBookingByUUID = $this->bookingCancellationService->getBookingByUuid($uuid);
        $oldBooking = $this->bookingCancellationService->getBooking($oldBookingByUUID, true);

        if (!$oldBooking->isBookingCancellable()) {
            $this->logger->info("Booking not cancellable: {$oldBooking->getId()}");
            throw new BookingNotCancellableException;
        }

        $originalBookingJson = json_decode($oldBookingByUUID->complete_booking_json, true) ?? [];

        // $newBookingByUUID is the persisted Eloquent row (exists=true), used to write DB updates.
        // $newBooking is the rich domain object (may be a fresh instance after confirmation) used for the response.
        [$newBookingByUUID, $newBooking] = $this->createPatchedBooking($requestParams, $uuid, $oldBookingByUUID, $oldBooking, $originalBookingJson);

        $bookingData = $this->transformer->transform($newBooking);
        $newBookingByUUID->update([
            'status' => $newBooking->getStatus(),
            'unit_items' => json_encode($newBooking->getUnits()),
            'complete_booking_json' => json_encode($bookingData),
        ]);

        $this->createBookingRebookAuditNote($newBooking, $oldBooking, $uuid);

        $this->cancelOldBookingOrNotify($oldBooking, $oldBookingByUUID, $newBooking);

        $this->logger->info(["message" => "Booking update request processed, returning response", "response" => $bookingData]);

        return $bookingData;
    }

    /**
     * @return array{0: Booking, 1: Booking} [persisted Eloquent row, rich domain object for the response]
     */
    protected function createPatchedBooking(
        array $requestParams,
        string $originalUuid,
        Booking $oldBookingByUUID,
        Booking $oldBooking,
        array $originalBookingJson
    ): array
    {
        $optionId = $requestParams[self::FIELD_OPTION_ID] ?? $originalBookingJson[self::FIELD_OPTION_ID] ?? $oldBookingByUUID->option_id;
        $this->optionService->validateOptionId($optionId);

        $productId = $originalBookingJson['productId'] ?? $oldBookingByUUID->product_id;
        $product = $this->productService->find($productId);
        $option = $product->getOptionById($optionId);

        $availabilityId = $requestParams[self::FIELD_AVAILABILITY_ID] ?? $originalBookingJson[self::FIELD_AVAILABILITY_ID] ?? $oldBookingByUUID->availability_id;
        $availability = $this->availabilityService->getAvailabilityObjectFromAvailabilityId($availabilityId);

        $unitItems = $requestParams[self::FIELD_UNIT_ITEMS] ?? $originalBookingJson[self::FIELD_UNIT_ITEMS] ?? json_decode($oldBookingByUUID->unit_items, true) ?? [];
        $this->unitService->validateUnitItems($unitItems, $productId);

        $notes = $requestParams[self::FIELD_NOTES] ?? $originalBookingJson[self::FIELD_NOTES] ?? null;
        $resellerReference = $requestParams[self::FIELD_RESELLER_REFERENCE] ?? $originalBookingJson[self::FIELD_RESELLER_REFERENCE] ?? null;
        $leadContactData = $requestParams[self::FIELD_CONTACT] ?? $originalBookingJson[self::FIELD_CONTACT] ?? null;

        // The new booking must keep the same public uuid as the old one, so the old
        // booking's uuid has to be freed up first before it can be reused.
        $this->freeUpOriginalUuid($oldBookingByUUID, $oldBooking);

        try {
            $newBookingByUUID = $this->bookingReservationService->reserve($product, $option, $availability, $unitItems, $originalUuid, $notes, null);

            if (!empty($resellerReference)) {
                $newBookingByUUID->setResellerReference($resellerReference);
            }

            if ($oldBooking->getStatus() === Booking::STATUS_ON_HOLD) {
                return [$newBookingByUUID, $newBookingByUUID];
            }

            // reserve() does not populate leadCustomerId (only available via TourCMS showBooking),
            // which confirmation needs in order to update the lead customer's contact details.
            $freshNewBooking = $this->bookingConfirmationService->getBooking($newBookingByUUID);
            $confirmedBooking = $this->confirmPatchedBooking($freshNewBooking, $unitItems, $leadContactData);
        } catch (Throwable $exception) {
            $this->restoreOriginalUuid($oldBookingByUUID, $oldBooking, $originalUuid);
            throw $exception;
        }

        return [$newBookingByUUID, $confirmedBooking];
    }

    /**
     * Add an audit note on the old booking pointing to the new booking that replaced it.
     * @param \App\Models\Booking $oldBooking
     * @param \App\Models\Booking $newBooking
     * @return void
     */
    protected function createBookingReplacedAuditNote(Booking $oldBooking, Booking $newBooking): void
    {
        $note = self::BOOKING_REPLACED_AUDIT_NOTE_PREFIX . $newBooking->getBookingId();
        $this->tourCMSService->callTourCMSAddNoteToBooking($oldBooking->getChannelId(), $oldBooking->getBookingId(), $note);
    }

    /**
     * Add an audit note on the new booking pointing back to the old booking it replaced.
     * @param \App\Models\Booking $newBooking
     * @param \App\Models\Booking $oldBooking
     * @param string $oldBookingOriginalUuid the uuid the old booking had before it was reassigned
     * @return void
     */
    protected function createBookingRebookAuditNote(Booking $newBooking, Booking $oldBooking, string $oldBookingOriginalUuid): void
    {
        $note = self::BOOKING_REBOOK_AUDIT_NOTE_PREFIX . "{$oldBooking->getBookingId()}, UUID: {$oldBookingOriginalUuid}";
        $this->tourCMSService->callTourCMSAddNoteToBooking($newBooking->getChannelId(), $newBooking->getBookingId(), $note);
    }

    /**
     * Reassign the booking_uuid TourCMS core holds for this booking to a new value.
     * @param \App\Models\Booking $booking
     * @param string $newUuid
     * @return void
     */
    protected function reassignBookingUuid(Booking $booking, string $newUuid): void
    {
        $bookingData = new SimpleXMLElement('<booking />');
        $bookingData->addChild('booking_id', $booking->getBookingId());
        $bookingData->addChild('booking_uuid', $newUuid);

        $this->tourCMSService->updateBooking($bookingData);
    }

    /**
     * Reassign the old booking's uuid to a newly generated one, both in TourCMS core
     * (via booking/update.xml) and in our local row, freeing up its original uuid so
     * the replacement booking can reuse it. Eloquent tracks the pre-change primary key
     * value internally, so ->save() still targets the correct row even though its
     * primary key value is what's being changed.
     */
    protected function freeUpOriginalUuid(Booking $oldBookingByUUID, Booking $oldBooking): void
    {
        $temporaryUuid = (string) Str::uuid();

        $this->reassignBookingUuid($oldBooking, $temporaryUuid);

        $oldBookingByUUID->setUuid($temporaryUuid);
        $oldBookingByUUID->save();
        $oldBooking->setUuid($temporaryUuid);
    }

    /**
     * Restore the old booking's original uuid, both in TourCMS core and locally.
     * Called whenever creating or confirming the replacement booking fails, so the
     * old booking is left exactly as it was before the update was attempted.
     */
    protected function restoreOriginalUuid(Booking $oldBookingByUUID, Booking $oldBooking, string $originalUuid): void
    {
        $this->reassignBookingUuid($oldBooking, $originalUuid);

        $oldBookingByUUID->setUuid($originalUuid);
        $oldBookingByUUID->save();
        $oldBooking->setUuid($originalUuid);
    }

    protected function confirmPatchedBooking(Booking $booking, array $unitItems, ?array $leadContactData): Booking
    {
        $unitContacts = $this->bookingContactService->createUnitContactsArray($unitItems);

        $originalLeadContact = $this->bookingContactService->createLeadTravellerContact($leadContactData);
        $leadContact = $originalLeadContact;

        $leadContact = $this->bookingContactService->updateBookingTravelersWithContactInfo($unitContacts, $booking, $leadContact);

        if (!empty($leadContact)) {
            $booking = $this->bookingContactService->updateBookingLeadCustomerContactInfo($booking, $leadContact);
        }

        $confirmedBooking = $this->bookingConfirmationService->confirmBooking($booking);

        if (!empty($originalLeadContact)) {
            $confirmedBooking->setContact($originalLeadContact);
        }

        return $confirmedBooking;
    }

    protected function cancelOldBookingOrNotify(Booking $oldBooking, Booking $oldBookingByUUID, Booking $newBooking): void
    {
        try {
            $this->cancelOldBooking($oldBooking, $oldBookingByUUID, $newBooking);
        } catch (Throwable $exception) {
            $this->notifyCancellationFailure($oldBooking, $newBooking, $exception);
        }
    }

    /**
     * Cancel (or delete) the old booking and record the replacement on it.
     * Throws if the cancellation itself fails, letting the caller decide how to react.
     */
    protected function cancelOldBooking(Booking $oldBooking, Booking $oldBookingByUUID, Booking $newBooking): void
    {
        if ($this->bookingCancellationService->shouldWeCancelBooking($oldBooking)) {
            $cancelled = $this->bookingCancellationService->cancelBooking($oldBooking);
        } else {
            $cancelled = $this->bookingCancellationService->deleteBooking($oldBooking);
        }

        if (true !== $cancelled) {
            throw new InvalidBookingUUIDException($oldBooking->getUuid());
        }

        $this->bookingCancellationService->updateBookingStatusToCancelled($oldBooking);
        $oldBooking->setCancellation(new BookingCancellation("Replaced by new booking, TourCMS ID: {$newBooking->getBookingId()}"));

        $oldBookingTransformed = $this->transformer->transform($oldBooking);
        $oldBookingByUUID->update(['complete_booking_json' => json_encode($oldBookingTransformed)]);

        $this->createBookingReplacedAuditNote($oldBooking, $newBooking);
    }

    /**
     * Log and email internal recipients when cancelling the old booking failed,
     * so the update itself doesn't fail on top of an already-failed cancellation.
     */
    protected function notifyCancellationFailure(Booking $oldBooking, Booking $newBooking, Throwable $exception): void
    {
        $this->logger->error([
            "message" => "Failed to cancel old booking after booking update, sending notification email",
            "oldBookingId" => $oldBooking->getId(),
            "newBookingId" => $newBooking->getId(),
            "exception" => $exception->getMessage(),
        ]);

        $internalEmails = array_filter(
            (array) json_decode(config('services.tcms.internal_emails'), true),
            fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)
        );

        if (empty($internalEmails)) {
            $this->logger->error([
                "message" => "No valid internal emails configured, skipping cancellation failure notification",
                "oldBookingId" => $oldBooking->getId(),
                "newBookingId" => $newBooking->getId(),
            ]);

            return;
        }

        Mail::to($internalEmails)
            ->send(new BookingCancellationFailedMail($oldBooking, $newBooking, $exception->getMessage()));
    }
}
