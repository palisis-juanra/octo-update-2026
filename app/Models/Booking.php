<?php

namespace App\Models;

use App\Models\Availability\Availability;
use App\Services\DateTimeService;
use App\Services\XMLService;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SimpleXMLElement;

class Booking extends Model
{
    use HasFactory;
    public const STATUS_ON_HOLD = 'ON_HOLD';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_REDEEMED = 'REDEEMED';
    public const TCMS_STATUS_QUOTATION = 0;
    public const TCMS_STATUS_PROVISIONAL = 1;
    public const TCMS_STATUS_CONFIRMED = 2;
    public const TCMS_STATUS_TEMPORARY = '-1';
    public const FIELD_VOUCHER = 'VOUCHER';
    public const FIELD_TICKET = 'TICKET';
    protected bool $testMode;
    protected ?string $utcCreatedAt;
    protected ?string $utcUpdatedAt;
    protected ?string $utcRedeemedAt;
    protected ?string $utcConfirmedAt;
    protected ?string $supplierReference;
    protected ?string $resellerReference;
    protected ?bool $cancellable;
    protected ?object $cancellation;
    protected Contact $contact;
    protected ?string $notes;
    protected ?Voucher $voucher;
    protected ?string $utcExpiresAt;
    protected ?int $expirationMinutes;
    protected Product $product;
    protected ?Option $option;
    protected Availability $availability;
    protected ?array $units;
    protected int $leadCustomerId;
    protected SimpleXMLElement $bookingData;
    protected array $completeBookingJson;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bookings';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id', 
        'account_id', 
        'channel_id',
        'availability_id',
        'product_id',
        'option_id',
        'unit_items',
        'complete_booking_json',
        'status',
    ];

    public static $snakeAttributes = true;

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
        $this->notes = null;
        $this->units = [];
        $this->completeBookingJson = [];
        $this->contact = new Contact;
        $this->voucher = null;
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

    public function getBookingId(): int
    {
        return $this->booking_id;
    }

    public function setUuid(string $uuid): self
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

    public function setUtcCreatedAt(int $createdAt): self
    {
        $creationDateTime = new DateTime('now', new DateTimeZone('UTC'));
        $creationDateTime->setTimestamp($createdAt);

        $this->utcCreatedAt = DateTimeService::getISO8601DateFormatted($creationDateTime);

        return $this;
    }

    public function getUtcCreatedAt(): ?string
    {
        return $this->utcCreatedAt;
    }

    public function setUtcUpdatedAt(string $utcUpdatedAt): self
    {
        $this->utcUpdatedAt = $utcUpdatedAt;

        return $this;
    }

    public function getUtcUpdatedAt(): ?string
    {
        return $this->utcUpdatedAt;
    }

    public function getUtcRedeemedAt(): ?string
    {
        return $this->utcRedeemedAt;
    }

    public function setUtcRedeemedAt(?int $redeemedAt): self
    {
        if (is_null($redeemedAt)) {
            $this->utcRedeemedAt = null;

            return $this;
        }

        $redeemDateTime = new DateTime('now', new DateTimeZone('UTC'));
        $redeemDateTime->setTimestamp($redeemedAt);
        $this->utcRedeemedAt = DateTimeService::getISO8601DateFormatted($redeemDateTime);

        return $this;  
    }

    public function getUtcConfirmedAt(): ?string
    {
        return $this->utcConfirmedAt;
    }

    /**
     * Set the value of utcConfirmedAt
     *
     * @return  self
     */ 
    public function setUtcConfirmedAt(?int $confirmedAt): self
    {
        if (is_null($confirmedAt)) {
            $this->utcConfirmedAt = null;
        }

        $confirmationDateTime = new DateTime('now', new DateTimeZone('UTC'));
        $confirmationDateTime->setTimestamp($confirmedAt);
        $this->utcConfirmedAt = DateTimeService::getISO8601DateFormatted($confirmationDateTime);

        return $this;
    }


    public function setUtcExpiresAt(?int $seconds): self
    {
        if (is_null($seconds)) {
            $this->utcExpiresAt = null;
            return $this;
        } 
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
        $this->product_id = $product->getId();
        $this->product = $product;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setOption(Option|null $option): self
    {
        $this->option_id = $option?->getId();
        $this->option = $option;

        return $this;
    }

    public function setOptionId(string|null $optionId): self
    {
        $this->option_id = $optionId;

        return $this;
    }

    public function getOption(): ?Option
    {
        return $this->option;
    }

    public function setAvailability(Availability $availability): self
    {
        $this->availability_id = $availability->getId();
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

    public function setResellerReference(string $resellerReference): self
    {
        $this->resellerReference = $resellerReference;

        return $this;
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

    public function setCancellable(bool $cancellable): self
    {
        $this->cancellable = $cancellable;

        return $this;
    }

    public function getCancellable(): bool
    {
        return $this->cancellable;
    }

    public function setCancellation(?object $cancellation): self
    {
        $this->cancellation = $cancellation;

        return $this;
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

    public function setExpirationMinutes(?int $expirationMinutes): self
    {
        $this->expirationMinutes = $expirationMinutes;

        return $this;
    }

    public function getExpirationMinutes(): ?int
    {
        return $this->expirationMinutes;
    }

    /**
     * Summary of setUnits
     * @param UnitItem[] $unitItems
     * @return Booking
     */
    public function setUnits(array|null $unitItems): self
    {
        $this->units = $unitItems;

        return $this;
    }

    public function setUnitItems(array|null $unitItems): self
    {
        $this->unit_items = json_encode($unitItems);

        return $this;
    }

    public function getUnits(): ?array
    {
        return $this->units;
    }

    public function getCompleteBookingJson(): ?array
    {
        return $this->completeBookingJson;
    }

    public function setCompleteBookingJson(string $completeBookingJson): self
    {
        $this->completeBookingJson = json_decode($completeBookingJson);

        return $this;
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

    public function setVoucher(?Voucher $voucher): self
    {
        $this->voucher = $voucher;
        
        return $this;
    }

    public function getVoucher(): ?Voucher
    {
        return $this->voucher;
    }

    public function setLeadCustomerId(int $leadCustomerId): self
    {
        $this->leadCustomerId = $leadCustomerId;

        return $this;
    }

    public function getLeadCustomerId(): ?int
    {
        return $this->leadCustomerId;
    }

    public function isBookingCancellable(): bool
    {
        return $this->getCancellable() == 1;
    }

    public static function createUtcCancelledAt(?int $cancelledAt): ?string
    {
        if (is_null($cancelledAt)) {
            return null;
        }

        $cancelledDateTime = new DateTime('now', new DateTimeZone('UTC'));
        $cancelledDateTime->setTimestamp($cancelledAt);
        return DateTimeService::getISO8601DateFormatted($cancelledDateTime);
    }

    /**
     * Get the value of bookingData
     */ 
    public function getBookingData(): SimpleXMLElement
    {
        return $this->bookingData;
    }

    public function setBookingData(SimpleXMLElement $bookingData): self
    {
        $this->bookingData = $bookingData;
        
        return $this;
    }

    public function isAlreadyConfirmed(): bool
    {
        return in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_REDEEMED, self::STATUS_CANCELLED]);
    }

    protected static function getFirstRedeemed(SimpleXMLElement $bookingData): ?string
    {
        $componentsRedeemed = [];
        $components = XMLService::getArrayFromXmlNode($bookingData->components, 'component');

        foreach ($components as $component) {
            if (!empty((string) $component->redeemed_at)) {
                $componentsRedeemed[] = (int) $component->redeemed_at_utc_seconds;
            }
        }

        if (empty($componentsRedeemed)) {
            return null;
        }

        sort($componentsRedeemed);

        return $componentsRedeemed[0];

    }
}