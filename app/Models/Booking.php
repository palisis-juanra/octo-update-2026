<?php

namespace App\Models;

use App\Models\Availability\Availability;
use App\Services\DateTimeService;
use App\Transformers\BaseTransformer;
use App\Transformers\ContactTransformer;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use SimpleXMLElement;
use stdClass;

class Booking extends Model
{
    use HasFactory;
    const STATUS_ON_HOLD = 'ON_HOLD';

    protected array $units;

    public function __construct()
    {
        $this->status = self::STATUS_ON_HOLD;
        $this->testMode = false;
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $this->utcCreatedAt = DateTimeService::getISO8601DateFormatted($now);
        $this->utcUpdatedAt = DateTimeService::getISO8601DateFormatted($now);
        $this->utcRedeemedAt = null;
        $this->utcConfirmedAt = null;
        $this->supplierReference = null;
        $this->resellerReference = null;
        $this->cancellable = false;
        $this->cancellation = null;
        $this->contact = new stdClass;
        $this->notes = null;
        $this->units = [];
        $this->contact = new Contact;
    }

    public static function createFromXML(
        SimpleXMLElement $startNewBookingData,
        Product $product,
        Option $option,
        Availability $availability,
        array $unitItems,
        ?string $notes = ''
    ): Booking
    {
        $booking = new Booking();

        $bookingData = $startNewBookingData->booking;

        $booking->setBookingId((int) $bookingData->booking_id);
        $booking->setUuid((string) $bookingData->booking_uuid);
        $booking->setAccountId((int) $bookingData->account_id);
        $booking->setChannelId((int) $bookingData->channel_id);
        $booking->setUtcExpiresAt((int) $bookingData->hold_time_seconds);
        $booking->setExpirationMinutes((int) $bookingData->hold_time_seconds / 60);
        $booking->setProduct($product);
        $booking->setOption($option);
        $booking->setAvailability($availability);
        $booking->setUnits($unitItems);
        
        if (!is_null($notes)){
            $booking->setNotes($notes);
        }

        return $booking;
    }

    public function getId(): string
    {
        return "{$this->account_id}|{$this->channel_id}|{$this->booking_id}";
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getAccountId(): ?int
    {
        return $this->account_id;
    }

    public function getChannelId(): ?int
    {
        return $this->channel_id;
    }

    public function setBookingId(int $bookingId): self
    {
        $this->booking_id = $bookingId;
        return $this;
    }

    public function setUuid(string $uuid): string
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function setAccountId(int $accountId): self
    {
        $this->account_id = $accountId;
        return $this;
    }

    public function setChannelId(int $channelId): self
    {
        $this->channel_id = $channelId;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTestMode(): bool
    {
        return $this->testMode;
    }

    public function getUtcCreatedAt(): ?string
    {
        return $this->utcCreatedAt;
    }

    public function getUtcUpdatedAt(): ?string
    {
        return $this->utcUpdatedAt;
    }

    public function getUtcRedeemedAt(): ?string
    {
        return $this->utcRedeemedAt;
    }

    public function getUtcConfirmedAt(): ?string
    {
        return $this->utcConfirmedAt;
    }


    public function setUtcExpiresAt(int $seconds): self
    {
        $timestamp = time() + $seconds;

        $expirationDateTime = new DateTime('now', new DateTimeZone('UTC'));
        $expirationDateTime->setTimestamp($timestamp);
        $this->utcExpiresAt = DateTimeService::getISO8601DateFormatted($expirationDateTime);

        return $this;
    }

    public function getUtcExpiresAt(): ?string
    {
        return $this->utcExpiresAt;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setOption(Option $option): self
    {
        $this->option = $option;

        return $this;
    }

    public function getOption(): ?Option
    {
        return $this->option;
    }

    public function setAvailability(Availability $availability): self
    {
        $this->availability = $availability;

        return $this;
    }

    public function getAvailability(): ?Availability
    {
        return $this->availability;
    }

    public function getResellerReference(): ?string
    {
        return $this->resellerReference;
    }

    public function setSupplierReference(string $supplierReference): self
    {
        $this->supplierReference = $supplierReference;

        return $this;
    }

    public function getSupplierReference(): ?string
    {
        return $this->supplierReference;
    }

    public function getCancellable(): bool
    {
        return $this->cancellable;
    }

    public function getCancellation(): ?object
    {
        return $this->cancellation;
    }

    public function setNotes(string $notes): self
    {
        $this->notes = $notes;
        
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setExpirationMinutes(int $expirationMinutes): self
    {
        $this->expirationMinutes = $expirationMinutes;

        return $this;
    }

    public function getExpirationMinutes(): ?int
    {
        return $this->expirationMinutes;
    }

    public function setUnits(array $unitItems): self
    {
        $option = $this->product->getOptionById($this->getOption()->getId());
        foreach ($unitItems as $unitItem) {
            $unitId = (string) $unitItem['unitId'];

            // TODO Create Model for UnitItem and its transformer
            $unit = $option->getUnitById($unitId);

            $newUnitItem = new stdClass;
            $newUnitItem->id = $unit->id;
            $newUnitItem->internalName = $unit->internalName;
            $newUnitItem->reference = $unit->reference;
            $newUnitItem->type = $unit->type;
            $newUnitItem->requiredContactFields = $unit->requiredContactFields;
            $newUnitItem->restrictions = $unit->restrictions;

            $newUnitItem->unit = $unit;

            $newUnitItem->unitId = $unit->id;
            $newUnitItem->uuid = Uuid::uuid4();
            $newUnitItem->status = 'ON_HOLD';
            $newUnitItem->utcRedeemedAt = $this->getUtcRedeemedAt();

            $contactTransformer = new ContactTransformer(BaseTransformer::FULL_TRANSFORM);
            $newUnitItem->contact = $contactTransformer->transform($this->getContact());
            $newUnitItem->ticket = [
                'redemptionMethod' => $this->getProduct()->getRedemptionMethod(),
                'utcRedeemedAt' => $this->getUtcRedeemedAt(),
                'deliveryOptions' => [
                    [
                        "deliveryFormat" => $this->getProduct()->getDeliveryFormats()[0],
                        "deliveryValue" => $this->getProduct()->getDeliveryFormats()[0]
                    ]
                ]
            ];

            $this->units[] = $newUnitItem;
        }

        return $this;
    }

    public function getUnits(): ?array
    {
        return $this->units;
    }

    public function setContact(Contact $contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

}