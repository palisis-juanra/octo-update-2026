<?php

namespace App\Services;

use App\Http\Middleware\OctoAuthentication;
use App\Models\Product;
use App\Transformers\BaseTransformer;
use App\Transformers\ProductTransformer;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use TourCMS\Utils\TourCMS;

class ProductService
{
    const AVAILABILITY_TYPE_START_TIME = 'START_TIME';
    const AVAILABILITY_TYPE_OPENING_HOURS = 'OPENING_HOURS';
    const AVAILABILITY_TYPES = [
        self::AVAILABILITY_TYPE_START_TIME,
        self::AVAILABILITY_TYPE_OPENING_HOURS
    ];
    const DELIVERY_FORMAT_QRCODE = 'QRCODE';
    const DELIVERY_FORMAT_CODE128A = 'CODE128A';
    const DELIVERY_FORMAT_PDF_URL = 'PDF_URL';
    const DELIVERY_FORMATS = [
        self::DELIVERY_FORMAT_QRCODE,
        self::DELIVERY_FORMAT_CODE128A,
        self::DELIVERY_FORMAT_PDF_URL
    ];
    const DELIVERY_METHOD_VOUCHER = 'VOUCHER';
    const DELIVERY_METHOD_TICKET = 'TICKET';
    const DELIVERY_METHODS = [
        self::DELIVERY_METHOD_VOUCHER,
        self::DELIVERY_METHOD_TICKET
    ];
    const REDEMPTION_METHOD_MANIFEST = 'MANIFEST';
    const REDEMPTION_METHOD_DIGITAL = 'DIGITAL';
    const REDEMPTION_METHOD_PRINT = 'PRINT';
    const REDEMPTION_METHODS = [
        self::REDEMPTION_METHOD_MANIFEST,
        self::REDEMPTION_METHOD_DIGITAL,
        self::REDEMPTION_METHOD_PRINT
    ];
    const CANCELLATION_CUTOFF_DEFAULT = '45 minutes';
    const AVAILABILITY_LOCAL_START_TIMES_DEFAULT = '00:00';
    const TIME_TYPE_STRICT = 'strict';
    const TIME_TYPE_STRICT_START = 'strict_start';
    const TIME_TYPE_OPENING_HOURS = 'opening_hours';

    public TourCMS $tourCMS;
    public ProductTransformer $productTransformer;
    protected $channel;
    public function __construct(Request $request)
    {
        $maid = $request->get(OctoAuthentication::FIELD_MAID);
        $APIKey = $request->get(OctoAuthentication::FIELD_API_KEY);
        $this->channel = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);
        
        $this->tourCMS = new TourCMS($maid, $APIKey, TourCMSService::RESPONSE_FORMAT_SIMPLEXML);
        // TODO: Change url back.
        // $this->tourCMS->set_base_url(env('API_BASE_URL'));
        $this->tourCMS->set_base_url('http://api.tourcms.local');
        $this->productTransformer = new ProductTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function getProductList(): array
    {
        $apiResponse = $this->tourCMS->search_tours("", $this->channel);
        if ($apiResponse->error == "OK") {
            $shouldSkip = false;
            $productList = [];
            foreach ($apiResponse->tour as $tour) {
                $id = $this->generateId();
                $internalName = (string) $tour->tour_name;
                //TODO: Set $reference to supplier_note when available.
                $reference = null;
                //TODO: Finish locale mapping method
                $locale = "";
                if (isset($tour->languages_spoken) && !empty($tour->languages_spoken)) {
                    $languages = explode(',', $tour->languages_spoken);
                    $locale = $this->parseLanguageCodeToLocale($languages[0]);
                } else if (isset($tour->country)) {
                    $locale = $this->parseLanguageCodeToLocale($tour->country);
                }
                $timeZone = "";
                if (isset($tour->start_timezone)) {
                    $timeZone = (string) $tour->start_timezone;
                } else {
                    $timeZone = (string) $tour->account_timezone;
                }
                // TODO: Currently unsupported - false by default
                $allowFreesale = false;
                // TODO: Currently unsupported - true by default
                $instantConfirmation = true;
                // TODO: Currently unsupported - true by default
                $availabilityRequired = true;
                // TODO: Currently unsupported - true by default
                $instantDelivery = true;
                // TODO: Currently unsupported, will work when search tours includes time_type
                $availabilityType = "";
                if (isset($tour->time_type)) {
                    if ($tour->time_type == self::TIME_TYPE_STRICT || $tour->time_type == self::TIME_TYPE_STRICT_START) {
                        $availabilityType = self::AVAILABILITY_TYPE_START_TIME;
                    } else if ($tour->time_type == self::TIME_TYPE_OPENING_HOURS) {
                        $availabilityType = self::AVAILABILITY_TYPE_OPENING_HOURS;
                    }
                }
                $deliveryFormats = [];
                if (isset($tour->delivery_formats)) {
                    foreach ($tour->delivery_formats as $deliveryFormat) {
                        array_push($deliveryFormats, (string) $deliveryFormat);
                    }
                }
                $deliveryMethods = [];
                if (isset($tour->delivery_methods)) {
                    foreach ($tour->delivery_methods as $deliveryMethod) {
                        array_push($deliveryMethods, (string) $deliveryMethod);
                    }
                }
                $redemptionMethod = "";
                if (isset($tour->redemption_method)) {
                    $redemptionMethod = (string) $tour->redemption_method;
                }
                // TODO: options are hard-coded right now, we should generate an option with the proper data when we define how to generate them.
                $options = [];

                $optionId = "DEFAULT";
                $optionDefault = false;
                $optionInternalName = "Internal name";
                $optionReference = "Reference";
                $optionAvailabilityLocalStartTimes = ['00:00'];
                $optionCancellationCutoff = self::CANCELLATION_CUTOFF_DEFAULT;
                $optionCancellationCutoffAmount = 1;
                $optionCancellationCutoffUnit = "hour";
                $optionRequiredContactFields = ['firstname'];
                $optionRestrictions = new stdClass();
                $optionRestrictions->minUnits = 0;
                $optionRestrictions->maxUnits = 10;

                $optionUnits = [];

                $unitId = "adult_38f3820-1243-12";
                $unitInternalName = "Adult(s)";
                $unitReference = "LR1-01-new";
                $unitType = "YOUTH";
                $unitRequiredContactFields = ['firstname'];
                $unitRestrictions = new stdClass();
                $unitRestrictions->minAge = 3;
                $unitRestrictions->maxAge = 17;
                $unitRestrictions->idRequired = false;
                $unitRestrictions->minQuantity = 2;
                $unitRestrictions->maxQuantity = 7;
                $unitRestrictions->paxCount = 1;
                $unitRestrictions->accompaniedBy = ['adult_38f3820-1243-12'];

                $unit = (object) [
                    'id' => $unitId,
                    'internalName' => $unitInternalName,
                    'reference' => $unitReference,
                    'type' => $unitType,
                    'requiredContactFields' => $unitRequiredContactFields,
                    'restrictions' => $unitRestrictions
                ];
                array_push($optionUnits, $unit);

                $option = (object) [
                    'id' => $optionId,
                    'default' => $optionDefault,
                    'internalName' => $optionInternalName,
                    'reference' => $optionReference,
                    'availabilityLocalStartTimes' => $optionAvailabilityLocalStartTimes,
                    'cancellationCutoff' => $optionCancellationCutoff,
                    'cancellationCutoffAmount' => $optionCancellationCutoffAmount,
                    'cancellationCutoffUnit' => $optionCancellationCutoffUnit,
                    'requiredContactFields' => $optionRequiredContactFields,
                    'restrictions' => $optionRestrictions,
                    'units' => $optionUnits
                ];
                array_push($options, $option);

                $product = new Product(
                    $id,
                    $internalName,
                    $reference,
                    $locale,
                    $timeZone,
                    $allowFreesale,
                    $instantConfirmation,
                    $instantDelivery,
                    $availabilityRequired,
                    $availabilityType,
                    $deliveryFormats,
                    $deliveryMethods,
                    $redemptionMethod,
                    $options
                );
                error_log(print_r($product, true));

                $transformedProduct = $this->productTransformer->transform($product);
                error_log(print_r($transformedProduct, true));

                array_push($productList, $transformedProduct);
            }
            return $productList;
        }
        return [];
    }

    protected function generateId(): string
    {
        return \Ramsey\Uuid\Uuid::uuid4();
    }

    //TODO
    protected function parseLanguageCodeToLocale(string $code): string
    {
        return $code;
    }
}