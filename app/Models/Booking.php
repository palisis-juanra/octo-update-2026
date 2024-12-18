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
use Ramsey\Uuid\Uuid;
use SimpleXMLElement;
use stdClass;

class Booking extends Model
{
    use HasFactory;
    const STATUS_ON_HOLD = 'ON_HOLD';
    const STATUS_EXPIRED = 'EXPIRED';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_CANCELLED = 'CANCELLED';

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
    protected ?array $voucher;
    protected ?string $utcExpiresAt;
    protected ?int $expirationMinutes;
    protected Product $product;
    protected Option $option;
    protected Availability $availability;
    protected array $units;
    protected int $leadCustomerId;

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
        'unit_items'
    ];

    protected $appends = [
        
    ];

    /*
    protected $guarded = [
        'testMode',
        'utcCreatedAt',
        'utcUpdatedAt',
        'utcRedeemedAt',
        'utcConfirmedAt',
        'supplierReference',
        'resellerReference',
        'cancellable',
        'cancellation',
        'contact',
        'notes',
        'voucher',
        'utcExpiresAt',
        'expirationMinutes',
        'product',
        'option',
        'availability',
        'updated_at',
        'created_at'
    ];
    */

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
        $this->contact = new Contact;
        $this->voucher = null;
    }

    public static function createFromStartNewBookingXML(
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

        
        if (in_array('VOUCHER', $product->getDeliveryMethods())) {
            $booking->setVoucher([
                'redemptionMethod' => $product->getRedemptionMethod(),
                'utcRedeemedAt' => null,
                'deliveryOptions' => [
                    "deliveryFormat" => $product->getDeliveryFormats()[0]
                ]
            ]);
        }


        return $booking;
    }

    public static function createFromShowBookingXML(
        string $bookingUuid,
        SimpleXMLElement $showBookingXML,
        Product $product,
        Option $option,
        Availability $availability,
        array $unitItems
    ): Booking
    {
        $booking = new Booking();

        $bookingData = $showBookingXML->booking;

        $booking->setBookingId((int) $bookingData->booking_id);
        $booking->setUuid((string) $bookingUuid);
        $booking->setAccountId((int) $bookingData->account_id);
        $booking->setChannelId((int) $bookingData->channel_id);

        $booking->setLeadCustomerId((int) $bookingData->lead_customer_id);

        $booking->setUtcCreatedAt((int) $bookingData->made_date_time_at_utc_seconds);
        $booking->setUtcExpiresAt($bookingData->expiry_date_at_utc_seconds ? (string) $bookingData->expiry_date_at_utc_seconds : null);
        $booking->setUtcConfirmedAt($bookingData->confirmed_at_utc_seconds ? (string) $bookingData->confirmed_at_utc_seconds : null);
        $booking->setUtcRedeemedAt(self::getFirstRedeemed($bookingData));

        $booking->setExpirationMinutes(null);

        $booking->setStatus(self::getBookingStatus($bookingData));
        $booking->setCancellable((bool) $bookingData->cancellable);

        if ((int) $bookingData->cancel_reason !== 0) {
            $cancelObject = new stdClass();
        
            $cancelObject->refund = "ALL";
            $cancelObject->reason = (string) $bookingData->cancel_text;
            $cancelObject->utcCancelledAt = self::createUtcCancelledAt((int) $bookingData->cancelled_at_utc_seconds);

            $booking->setCancellation($cancelObject);
        }
        
        $booking->setProduct($product);
        $booking->setOption($option);
        $booking->setAvailability($availability);
        $booking->setUnits($unitItems);
        
        if (in_array('VOUCHER', $product->getDeliveryMethods())) {
            $booking->setVoucher([
                'redemptionMethod' => $product->getRedemptionMethod(),
                'utcRedeemedAt' => null,
                'deliveryOptions' => [
                    "deliveryFormat" => $product->getDeliveryFormats()[0]
                ]
            ]);
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

    public function setOption(Option $option): self
    {
        $this->option_id = $option->getId();
        $this->option = $option;

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

    public function setUnits(array $unitItems): self
    {
        $option = $this->product->getOptionById($this->getOption()->getId());
        foreach ($unitItems as $unitItem) {
            $unitId = (string) $unitItem['unitId'];

            // TODO Create Model for UnitItem and its transformer
            $unit = $option->getUnitById($unitId);

            $newUnitItem = new stdClass;
            $newUnitItem->uuid = Uuid::uuid4();
            $newUnitItem->resellerReference = null;
            $newUnitItem->supplierReference = $unit->reference;
            $newUnitItem->unitId = $unit->id;
            $newUnitItem->id = $unit->id;
            $newUnitItem->unit = $unit;

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
        $this->unit_items = json_encode($this->units);

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

    public function setVoucher(?array $voucher): self
    {
        $this->voucher = $voucher;
        
        return $this;
    }

    public function getVoucher(): ?array
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

    protected static function getBookingStatus(SimpleXMLElement $bookingData): string
    {
        if ((int) $bookingData->cancel_reason !== 0) {
            return Booking::STATUS_CANCELLED;
        }

        return (int) $bookingData->status == 2 ? Booking::STATUS_CONFIRMED : Booking::STATUS_ON_HOLD;

    }

    protected static function getFirstRedeemed(SimpleXMLElement $bookingData): ?string
    {
        $componentsRedeemed = [];
        $components = self::getArrayFromXmlNode($bookingData->components, 'component');

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

    protected static function createUtcCancelledAt(?int $cancelledAt): ?string
    {
        if (is_null($cancelledAt)) {
            return null;
        }

        $cancelledDateTime = new DateTime('now', new DateTimeZone('UTC'));
        $cancelledDateTime->setTimestamp($cancelledAt);
        return DateTimeService::getISO8601DateFormatted($cancelledDateTime);
    }

    protected static function getArrayFromXmlNode(SimpleXMLElement $parent, string $childName = ''): array
    {
        $children = [];
        foreach ($parent->children() as $child) {
            if (empty($childName) || $child->getName() == $childName) {
                $children[] = $child;
            }
        }
        return $children;
    }
}