<?php

namespace App\Services;

use App\Builders\BookingBuilder;
use App\Exceptions\InvalidProductContentException;
use App\Exceptions\InvalidProductIdException;
use App\Exceptions\NoMatchingDataException;
use App\Facades\OctoRequestFacade;
use App\Http\Requests\OctoRequest;
use App\Models\Location;
use App\Models\Media;
use App\Models\Option;
use App\Models\OptionContent;
use App\Models\Place;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductContent;
use App\Models\ProductMapping;
use App\Models\Unit;
use App\Models\UnitRestrictions;
use App\Transformers\BaseTransformer;
use App\Transformers\ProductTransformer;
use JsonException;
use SimpleXMLElement;
use stdClass;

class ProductService
{
    public const ENDPOINT_NAME = 'products';
    public const API_RESPONSE_OK = 'OK';
    public const API_RESPONSE_NO_MATCHING_DATA = 'NO MATCHING DATA';
    public const PRODUCT_ID_REGEX = '/^([A-Z]{2}_\d+_\d+\|\d+)$/';
    public const PRODUCT_ID_SEPARATOR_REGEX = '/[\||_]/';
    public const PRODUCT_ID_PIPE_SEPARATOR = '|';
    public const PRODUCT_ID_UNDERSCORE_SEPARATOR = '_';
    public const AVAILABILITY_TYPE_START_TIME = 'START_TIME';
    public const AVAILABILITY_TYPE_OPENING_HOURS = 'OPENING_HOURS';
    public const AVAILABILITY_TYPES = [
        self::AVAILABILITY_TYPE_START_TIME,
        self::AVAILABILITY_TYPE_OPENING_HOURS
    ];

    public const string DELIVERY_FORMAT_QRCODE = 'QRCODE';
    public const string DELIVERY_FORMAT_CODE128 = 'CODE128';
    public const string DELIVERY_FORMAT_PDF_URL = 'PDF_URL';
    public const array DELIVERY_FORMATS = [
        'QR_CODE' => self::DELIVERY_FORMAT_QRCODE,
        'PDF_URL' => self::DELIVERY_FORMAT_PDF_URL,
        'CODE128' => self::DELIVERY_FORMAT_CODE128,
        'CODE_128' => self::DELIVERY_FORMAT_CODE128
    ];
    public const DELIVERY_METHOD_VOUCHER = 'VOUCHER';
    public const DELIVERY_METHOD_TICKET = 'TICKET';
    public const DELIVERY_METHODS = [
        self::DELIVERY_METHOD_VOUCHER,
        self::DELIVERY_METHOD_TICKET
    ];
    public const TCMS_DELIVERY_METHOD_TICKET = 'TICKET';
    public const TCMS_DELIVERY_METHOD_TOUR = 'TOUR';
    public const TCMS_DELIVERY_METHOD_BOOKING = 'BOOKING';
    public const TCMS_DELIVERY_METHODS = [
        self::TCMS_DELIVERY_METHOD_TICKET,
        self::TCMS_DELIVERY_METHOD_TOUR,
        self::TCMS_DELIVERY_METHOD_BOOKING
    ];
    public const REDEMPTION_METHOD_MANIFEST = 'MANIFEST';
    public const REDEMPTION_METHOD_DIGITAL = 'DIGITAL';
    public const REDEMPTION_METHOD_PRINT = 'PRINT';
    public const REDEMPTION_METHODS = [
        self::REDEMPTION_METHOD_MANIFEST,
        self::REDEMPTION_METHOD_DIGITAL,
        self::REDEMPTION_METHOD_PRINT
    ];
    public const CUTOFF_TYPE_BEFORE_START_SEC = 'before_start_sec';
    public const CUTOFF_TYPE_DAY_BEFORE_TIME = 'day_before_time';
    public const CUTOFF_TYPE_SAME_DAY_TIME = 'same_day_time';
    public const CANCELLATION_CUTOFF_UNIT_MINUTE = 'minute';
    public const CANCELLATION_CUTOFF_UNIT_HOUR = 'hour';
    public const CANCELLATION_CUTOFF_UNIT_DAY = 'day';
    public const CANCELLATION_CUTOFF_UNITS = [
        'm' => self::CANCELLATION_CUTOFF_UNIT_MINUTE,
        'h' => self::CANCELLATION_CUTOFF_UNIT_HOUR,
        'd' => self::CANCELLATION_CUTOFF_UNIT_DAY
    ];
    public const CANCELLATION_CUTOFF_UNIT_DEFAULT = self::CANCELLATION_CUTOFF_UNIT_MINUTE;
    public const CANCELLATION_CUTOFF_AMOUNT_DEFAULT = 45;
    public const CANCELLATION_CUTOFF_NON_REFUNDABLE = 'Non refundable';
    public const CANCELLATION_CUTOFF_AMOUNT_NON_REFUNDABLE = 3650; // 10 years
    public const AVAILABILITY_LOCAL_START_TIMES_DEFAULT = '00:00';
    public const TIME_TYPE_STRICT = 'strict';
    public const TIME_TYPE_STRICT_START = 'strict_start';
    public const TIME_TYPE_OPENING_HOURS = 'opening_hours';
    public const UNIT_TYPE_ADULT = 'ADULT';
    public const UNIT_TYPE_YOUTH = 'YOUTH';
    public const UNIT_TYPE_CHILD = 'CHILD';
    public const UNIT_TYPE_INFANT = 'INFANT';
    public const UNIT_TYPE_SENIOR = 'SENIOR';
    public const UNIT_TYPES = [
        'a' => self::UNIT_TYPE_ADULT,
        'y' => self::UNIT_TYPE_YOUTH,
        'c' => self::UNIT_TYPE_CHILD,
        'i' => self::UNIT_TYPE_INFANT,
        's' => self::UNIT_TYPE_SENIOR
    ];
    public const UNIT_TYPES_ADULTS = [
        self::UNIT_TYPE_SENIOR,
        self::UNIT_TYPE_ADULT
    ];
    public const UNIT_TYPES_CHILDREN = [
        self::UNIT_TYPE_INFANT,
        self::UNIT_TYPE_CHILD
    ];
    public const CONTACT_FIELD_FIRST_NAME = 'firstName';
    public const CONTACT_FIELD_LAST_NAME = 'lastName';
    public const CONTACT_FIELD_PHONE_NUMBER = 'phoneNumber';
    public const CONTACT_FIELDS = [
        'firstname' => self::CONTACT_FIELD_FIRST_NAME,
        'surname' => self::CONTACT_FIELD_LAST_NAME,
        'tel_mobile' => self::CONTACT_FIELD_PHONE_NUMBER,
    ];
    public const MAPPING_STRUCTURE_TYPE_NOTSET = 'NOTSET';
    public const MAPPING_STRUCTURE_TYPE_SINGLE = 'SINGLE';
    public const MAPPING_STRUCTURE_TYPE_START_TIME = 'START_TIME';
    public const MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE = 'SUPPLIER_NOTE';
    public const MAPPING_STRUCTURE_TYPE_DEPARTURE_CODE = 'DEPARTURE_CODE';
    public const MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME = 'SUPPLIER_NOTE_PLUS_START_TIME';
    public const START_TIME_MULTI = 'MULTI';
    public const DAY_IN_SECONDS = 86400;
    public const HOUR_IN_SECONDS = 3600;
    public const MINUTE_IN_SECONDS = 60;
    public const DATE_TIME_TOMORROW = 'tomorrow';
    public const REQUIRED_FIELD_SCOPE_ALLPAX = 'allpax';
    public const REQUIRED_FIELD_SCOPE_LEADPAX = 'leadpax';
    public const REQUIRED_FIELD_SCOPE_OTHERPAX = 'otherpax';
    public const LOCALE_CODE_DEFAULT = 'en-GB';
    public const MAX_BOOKING_SIZE = 10;
    public const MIN_BOOKING_SIZE = 1;
    public const ERROR_AVAILABILITY_TYPE_MISSING = 'availabilityType field is missing';
    public const ERROR_DEPARTURE_STRUCTURE_NOT_SET = 'the tour departure structure is not set';
    public const ERROR_REFERENCE_MISSING = 'reference field is missing';
    public const ERROR_TIMEZONE_MISSING = 'timeZone field is missing';
    public const ERROR_TOUR_MAPPING_MISSING = 'the tour mapping is missing';  
    public const ERROR_TOUR_WITH_NO_OPTIONS = 'tour has no option available';
    public const ERROR_GROUP_PRICING_NOT_SUPPORTED = "Group pricing products are not supported";
    public const ERROR_FREESALE_NOT_SUPPORTED = "Freesale products are not supported";
    public const ERROR_HOTELS_NOT_SUPPORTED = "Hotels products are not supported";
    public const FEATURE_TYPE_INCLUSION = 'INCLUSION';
    public const FEATURE_TYPE_EXCLUSION = 'EXCLUSION';
    public const FEATURE_TYPE_HIGHLIGHT = 'HIGHLIGHT';
    public const FEATURE_TYPE_PREARRIVAL_INFORMATION = 'PREARRIVAL_INFORMATION';
    public const FEATURE_TYPE_REDEMPTION_INSTRUCTION = 'REDEMPTION_INSTRUCTION';
    public const FEATURE_TYPE_CANCELLATION_TERM = 'CANCELLATION_TERM';
    public const FEATURES_TYPES = [
        self::FEATURE_TYPE_INCLUSION => 'inc',
        self::FEATURE_TYPE_EXCLUSION => 'ex',
        self::FEATURE_TYPE_HIGHLIGHT => 'exp',
        self::FEATURE_TYPE_PREARRIVAL_INFORMATION => 'essential',
        self::FEATURE_TYPE_REDEMPTION_INSTRUCTION => 'redeem'
    ];
    public const array CONTENT_FIELDS_TO_SPLIT = ['inc', 'ex', 'exp', 'essential'];
    public const DEFAULT_DURATION_MINUTES = 60;
    public const TIMEZONE_NOT_SET = 'NOTSET';
    public const TIME_NOT_SET = 'NOTSET';
    public const DEFAULT_CHANNEL_LANG = 'en';
    public const DEFAULT_CHANNEL_COUNTRY = 'GB';
    public const IDENTIFIER_TYPE_GOOGLE_PLACE_ID = 'googlePlaceId';
    public const string OPTION_TITLE_DEFAULT = "DEFAULT";
  
    public ProductTransformer $productTransformer;

    private $errors = [];
    private $info = [];

    public function __construct(
        public TourCMSService $tourCMSService, 
        public JSONLogService $logger, 
        public LocaleService $localeService,
        public ProductMappingFactory $productMappingFactory,
        public TourPromotionService $tourPromotionService)
    {
        $this->productTransformer = new ProductTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function getProductList(string $channelId): array
    {
        $tourList = $this->getTourListData($channelId);
        $productList = [];
            foreach ($tourList as $tour) {
                if ($this->isTourValidForProductList($tour)) {
                    try {
                        $product = $this->createProductFromTourXML($tour);
                        $productList[] = $product;
                    } catch (InvalidProductContentException $e) {
                        continue;
                    }
                }                
            }
        return $productList;
    }

    public function find(string $productId, array $productArrayFromDB = []): Product
    {
        $tour = $this->findTourDataFromAPI($productId);
        // TODO Uncomment if needed
        //$this->logger->info(["message" => "Show tour response", "APIResponse" => $tour]);

        if ($this->getTourDistributionIdentifierFromProductId($productId) != (string) $tour->distribution_identifier) {
            throw new InvalidProductIdException($productId);
        }

        if (!empty($productArrayFromDB)) {
            $product = BookingBuilder::buildProductFromJSON($productArrayFromDB, $this->isVolumePricing($tour));
        } else {
            $product = $this->createProductFromTourXML($tour);
        }
        
        return $product;
    }

    public function createProductFromTourXML(SimpleXMLElement $tour): Product
    {
        $this->clearInfoAndErrors();
        $id = $this->buildProductId($tour);
        $internalName = (string) $tour->tour_name_long;
        $reference = null;
        if (isset($tour->supplier_tour_code)) {
            $reference = $tour->supplier_tour_code;
        } else {
            $this->info[] = self::ERROR_REFERENCE_MISSING;
        }
        $locale = $this->getProductLocale();
        $timeZone = $this->getProductTimeZone($tour);
        // Currently unsupported - false by default
        $allowFreesale = false;
        // Currently unsupported - true by default
        $instantConfirmation = true;
        // Currently unsupported - true by default
        $availabilityRequired = true;
        // Currently unsupported - true by default
        $instantDelivery = true;
        $availabilityType = $this->getProductAvailabilityType($tour);
        $deliveryFormats = $this->getProductDeliveryFormats($tour);
        $deliveryMethods = $this->getProductDeliveryMethods($tour);
        $redemptionMethod = $this->getProductRedemptionMethod($tour);

        if ($this->isGroupPricing($tour)) {
            $this->errors[] = self::ERROR_GROUP_PRICING_NOT_SUPPORTED;
        }

        if ($this->isHotel($tour)) {
            $this->errors[] = self::ERROR_HOTELS_NOT_SUPPORTED;
        }

        if ($this->isFreesale($tour)) {
            $this->errors[] = self::ERROR_FREESALE_NOT_SUPPORTED;
        }

        $options = [];
        if (isset($tour->tour_departure_structure->type)) {
            $options = $this->getProductOptions($tour);
            if (empty($options)) {
                $this->errors[] = self::ERROR_TOUR_WITH_NO_OPTIONS;
            }
        } else {
            $this->errors[] = self::ERROR_TOUR_MAPPING_MISSING;
        }

        if (count($this->errors) != 0) {
            $errorString = implode(', ', $this->errors);
            $this->logError("The content of the product is invalid: {$errorString}", null, ['productId' => $id]);
            throw new InvalidProductContentException($id, "The content of the product is invalid: {$errorString}");
        }

        if (count($this->info) != 0) {
            $infoString = implode(', ', $this->info);
            $this->logInfo("The product is missing some non-critical information: {$infoString}", null, ['productId' => $id]);
        }
        $minBookingSize = (int)$tour->min_booking_size <= 0 ? self::MIN_BOOKING_SIZE : (int)$tour->min_booking_size;
        $maxBookingSize = (int)$tour->max_booking_size <= 0 ? self::MAX_BOOKING_SIZE : (int)$tour->max_booking_size;

        $allDay = $this->isOpeningHours($tour);

        $product = new Product();
        $product->setId($id)
                ->setTourId((string) $tour->tour_id)
                ->setInternalName($internalName)
                ->setReference($reference)
                ->setLocale($locale)
                ->setTimeZone($timeZone)
                ->setAllowFreesale($allowFreesale)
                ->setInstantConfirmation($instantConfirmation)
                ->setInstantDelivery($instantDelivery)
                ->setAvailabilityRequired($availabilityRequired)
                ->setAvailabilityType($availabilityType)
                ->setDeliveryFormats($deliveryFormats)
                ->setDeliveryMethods($deliveryMethods)
                ->setRedemptionMethod($redemptionMethod)
                ->setOptions($options)
                ->setCutoff((array) $tour->cutoff)
                ->setMinBookingSize($minBookingSize)
                ->setMaxBookingSize($maxBookingSize)
                ->setAllDay($allDay);
        
        if ($this->isVolumePricing($tour)) {
            $product->setPricingType(Product::PRICING_TYPE_VOLUME);
        }

        if (true === OctoRequestFacade::isCapabilityActive(capability: OctoRequest::CAPABILITIES_CONTENT)) {

            $productContent = new ProductContent();
            
            $features = $this->getProductFeatures($tour);
            $media = $this->getProductMedia($tour);
            $locations = $this->getProductLocations($tour);
            $categoryLabels = CategoryLabelService::getFromTourXML($tour);
            $durationMinutesFrom = $this->getDurationMinutesFrom($tour);
            
            $productContent
                ->setTitle((string) $tour->tour_name_long)
                ->setShortDescription((string) $tour->shortdesc)
                ->setDescription($tour->longdesc)
                ->setFeatures($features)
                ->setMedia($media)
                ->setLocations($locations)
                ->setCategoryLabels($categoryLabels)
                ->setDurationMinutesFrom($durationMinutesFrom);
            $product->setContent($productContent);
        }

        if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_PRICING)) {
            $saleCurrency = !empty($tour->sale_currency) ? (string) $tour->sale_currency : (string) $this->tourCMSService->showChannel()->channel->sale_currency;            
            $product->setDefaultCurrency($saleCurrency);
            $product->setAvailableCurrencies([$saleCurrency]);
        }

        if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_BOOKINGCOM_RATES)) {
            $promotions = $this->tourPromotionService->getTourPromotions($product->getTourId());
            $product->setPromotions($promotions); 
        }

        return $product;
    }

    /**
     * @param SimpleXMLElement $tour
     * @return Option[]
     */
    public function getProductOptions(SimpleXMLElement $tour): array
    {

        $structureType = $tour->tour_departure_structure->type ? (string) $tour->tour_departure_structure->type : null;

        if (empty($structureType) || $structureType == self::MAPPING_STRUCTURE_TYPE_NOTSET) {
            $this->errors[] = self::ERROR_DEPARTURE_STRUCTURE_NOT_SET;
            return [];
        }

        $options = []; 
        $mappings = $this->productMappingFactory->create($tour);

        foreach ($mappings as $mapping) {

            $optionId = "{$structureType}";
            $availabilityStartTimes = $mapping->getStartTimes();

            if (!in_array($structureType, [self::MAPPING_STRUCTURE_TYPE_SINGLE, self::MAPPING_STRUCTURE_TYPE_START_TIME])) {
                $optionId .= "|{$mapping->getValue()}";
            }

            $optionDefault = in_array($structureType, [self::MAPPING_STRUCTURE_TYPE_SINGLE, self::MAPPING_STRUCTURE_TYPE_START_TIME]) ? true : false;
            
            $optionInternalName = "";
            $optionInternalName = $tour->tour_name ? (string) $tour->tour_name : '';
            if (!in_array($structureType, [self::MAPPING_STRUCTURE_TYPE_SINGLE, self::MAPPING_STRUCTURE_TYPE_START_TIME])) {
                $optionInternalName .= " - {$mapping->getValue()}";
            }

            if (empty($optionInternalName)){
                $this->info[] = "option {$optionId} internalName field is missing";
            }

            $optionReference = isset($tour->supplier_tour_code) ? (string) $tour->supplier_tour_code : null;
            if (empty($optionReference)){
                $this->info[] = "option {$optionId} reference field is missing";
            }

            if (empty($availabilityStartTimes) && (false === $this->isOpeningHours($tour))) {
                $this->info[] = "option {$optionId} availabilityLocalStartTimes fields are not present because of invalid mapping structure";
            }
            $optionCancellationCutoffUnit = self::CANCELLATION_CUTOFF_UNIT_DEFAULT;
            $optionCancellationCutoffAmount = self::CANCELLATION_CUTOFF_AMOUNT_DEFAULT;
            $optionCancellationCutoff = "{$optionCancellationCutoffAmount} {$optionCancellationCutoffUnit}s";
            if (isset($tour->non_refundable) && (int) $tour->non_refundable == 1) {
                $optionCancellationCutoffAmount = self::CANCELLATION_CUTOFF_AMOUNT_NON_REFUNDABLE;
                $optionCancellationCutoffUnit = self::CANCELLATION_CUTOFF_UNIT_DAY;
                $optionCancellationCutoff = self::CANCELLATION_CUTOFF_NON_REFUNDABLE;
            } else if (isset($tour->cancellation_policy) && isset($tour->cancellation_policy->policy)) {
                $cancellationPoliciesFromXML = XMLService::getArrayFromXmlNode($tour->cancellation_policy, 'policy');
                $policy = $cancellationPoliciesFromXML[0] ?? null;
                if ((isset($policy->type) && !empty($policy->type)) && (isset($policy->value) && !empty($policy->value))) {
                    $optionCancellationCutoffUnit = self::CANCELLATION_CUTOFF_UNITS[(string) $policy->type];
                    $optionCancellationCutoffAmount = (int) $policy->value;
                    $optionCancellationCutoff = "{$optionCancellationCutoffAmount} {$optionCancellationCutoffUnit}";
                    if ($optionCancellationCutoffAmount != 1) {
                        $optionCancellationCutoff .= "s";
                    }
                } else {
                    $this->info[] = "option {$optionId} cancellationCutoff info is empty or missing";
                }
            } else {
                $this->info[] = "option {$optionId} cancellationCutoff field is missing";
            }
            $optionRequiredContactFields = $this->getOptionRequiredContactFields($tour);
            $optionRestrictions = new stdClass();
            $optionRestrictions->minUnits = null;
            if (isset($tour->min_booking_size)) {
                $optionRestrictions->minUnits = (int) $tour->min_booking_size;
            }
            $optionRestrictions->maxUnits = null;
            if (isset($tour->max_booking_size)) {
                $optionRestrictions->maxUnits = (int) $tour->max_booking_size;
            }
    
            $optionUnits = $this->getOptionUnits($tour);

            if (true == $this->isOpeningHours($tour)) {
                $availabilityStartTimes = [];
            }

            $optionData = new stdClass();
            $optionData->id = $optionId;
            $optionData->default = $optionDefault;
            $optionData->internalName = $optionInternalName;
            $optionData->reference = $optionReference;
            $optionData->availabilityLocalStartTimes = $availabilityStartTimes;
            $optionData->cancellationCutoff = $optionCancellationCutoff;
            $optionData->cancellationCutoffAmount = $optionCancellationCutoffAmount;
            $optionData->cancellationCutoffUnit = $optionCancellationCutoffUnit;
            $optionData->requiredContactFields = $optionRequiredContactFields;
            $optionData->restrictions = $optionRestrictions;
            $optionData->units = $optionUnits;
            
            $option = Option::create($optionData);
            
            $optionContent = new OptionContent($this->getOptionTitle((string) $tour->tour_name_long, $mapping));
            $option->setContent($optionContent);
            
            $options[] = $option;
        }

        return $options;
    }

    public function getOptionTitle(string $tourName, ProductMapping $productMapping): string
    {
        if (in_array($productMapping->getType(), [self::MAPPING_STRUCTURE_TYPE_SINGLE, self::MAPPING_STRUCTURE_TYPE_START_TIME])) {
            return self::OPTION_TITLE_DEFAULT;
        }

        if (empty($productMapping->getLabel())) {
            return $tourName;
        }

        $showChannel = $this->tourCMSService->showChannel();
        $channelLanguage = (string) $showChannel->channel->lang ?? 'en';
        
        try {
            $labelAsJson = json_decode($productMapping->getLabel(), true, flags: JSON_THROW_ON_ERROR);
            if (is_array($labelAsJson)) {
                if (array_key_exists($channelLanguage, $labelAsJson) && !empty($labelAsJson[$channelLanguage])) {
                    return (string) $labelAsJson[$channelLanguage];
                } elseif (array_key_exists('en', $labelAsJson) && !empty($labelAsJson['en'])) {
                    return (string) $labelAsJson['en'];
                }
            }
        } catch (JsonException) {
            return $productMapping->getLabel();
        }
        

        return $tourName;
    }

    public function getOptionUnits(SimpleXMLElement $tour): array
    {
        $optionUnits = [];
        $ratesFromXML = $this->getRatesFromXML($tour);
        $adultRates = $this->getAdultRates($tour);
        $permitChildOnly = (int) $tour->tour_permit_child_only == 1;

        if (count($ratesFromXML) > 0) {
            foreach ($ratesFromXML as $rate) {
                $unitId = UnitService::buildUnitId((string) $tour->distribution_identifier, (string) $tour->channel_id, (string) $rate->rate_id);
                $unitInternalName = "";
                if (isset($rate->rate_id)) {
                    $unitInternalName = (string) $rate->rate_id;
                } else {
                    $this->info[] = "unit {$unitId} internalName field is missing";
                }
                $unitReference = null;
                if (isset($rate->rate_code)) {
                    $unitReference = (string) $rate->rate_code;
                } else {
                    $this->info[] = "unit {$unitId} reference field is missing";
                }
                $unitType = "";
                if (isset($rate->agecat)) {
                    $agecat = (string) $rate->agecat;
                    if (array_key_exists($agecat, self::UNIT_TYPES)) {
                        $unitType = self::UNIT_TYPES[$agecat];
                    } else {
                        $this->info[] = "unit {$unitId} invalid type: {$agecat}";
                    }
                } else {
                    $this->info[] = "unit {$unitId} type field is missing";
                }
                $unitRequiredContactFields = $this->getUnitRequiredContactFields($tour);


                $unitRestrictions = new UnitRestrictions();

                if (isset($rate->agerange_min)) {
                    $unitRestrictions->setMinAge((int) $rate->agerange_min);
                } else {
                    $this->info[] = "unit {$unitId} restrictions minAge field is missing";
                }

                if (isset($rate->agerange_max)) {
                    $unitRestrictions->setMaxAge((int) $rate->agerange_max);
                } else {
                    $this->info[] = "unit {$unitId} restrictions maxAge field is missing";
                }

                if (isset($rate->minimum)) {
                    $unitRestrictions->setMinQuantity((int) $rate->minimum);
                }

                if (isset($rate->maximum)) {
                    $unitRestrictions->setMaxQuantity((int) $rate->maximum);
                }
                $unitRestrictions->setAccompaniedBy($this->getAccompaniedBy($permitChildOnly, $unitType, $adultRates));

                $unit = new Unit();
                $unit->setId($unitId);
                $unit->setInternalName($unitInternalName);
                $unit->setReference($unitReference);
                $unit->setType($unitType);
                $unit->setRequiredContactFields($unitRequiredContactFields);
                $unit->setRestrictions($unitRestrictions);
                $unit->setRateId((string) $rate->rate_id);

                if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_CONTENT)) {
                    $unit->setTitle((string) $rate->label_1);
                }

                if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_PRICING)) {

                    $saleCurrency = !empty($tour->sale_currency) ? (string) $tour->sale_currency : (string) $this->tourCMSService->showChannel()->channel->sale_currency;
                    if (Product::PRICING_PER_UNIT === $this->getPricingPerFromTourXML($tour)) {
                        $unitPricing = new Pricing(
                            100 * $rate->from_price,
                            100 * $rate->from_price,
                            null,
                            $saleCurrency
                        );
                        $unit->setPricing($unitPricing);
                    }
                }

                $optionUnits[] = $unit;
            }
        }
        return $optionUnits;
    }

    public function transform(Product $product): array
    {
        return $this->productTransformer->transform($product);
    }

    public function transformList(array $productList): array
    {
        $transformedProductList = [];
        foreach ($productList as $product) {
            $transformedProduct = $this->productTransformer->transform($product);
            $transformedProductList[] = $transformedProduct;
        }
        return $transformedProductList;
    }

    public function getTourListData(string $channelId): array
    {
        $apiResponse = $this->tourCMSService->listTours($channelId, "?".TourCMSService::LIST_TOURS_EXTENDED_TOUR_INFO_PARAM);

        if (!isset($apiResponse->tour)) {
            return [];
        }

        $toursFromXML = XMLService::getArrayFromXmlNode($apiResponse, 'tour');
        return $toursFromXML;
    }

    public function getRatesFromXML(SimpleXMLElement $tour): array
    {
        $ratesFromXML = [];
        $parent = null;
        if (isset($tour->new_booking->people_selection)) {
            $parent = $tour->new_booking->people_selection;
        } else if (isset($tour->people_selection)) {
            $parent = $tour->people_selection;
        } else if (is_null($parent)) {
            return $ratesFromXML;
        }
        return XMLService::getArrayFromXmlNode($parent, 'rate');
    }

    public function getAdultRates(SimpleXMLElement $tour): array
    {
        $adultRates = [];
        $ratesFromXML = $this->getRatesFromXML($tour);
        foreach ($ratesFromXML as $rate) {
            if (isset($rate->agecat) && in_array(self::UNIT_TYPES[(string) $rate->agecat], self::UNIT_TYPES_ADULTS)) {
                $adultRates[] = UnitService::buildUnitId((string) $tour->distribution_identifier, (string) $tour->channel_id, (string) $rate->rate_id);
            }
        }
        return $adultRates;
    }

    public function getAccompaniedBy(bool $permitChildOnly, string $unitType, array $adultRates): array
    {
        if ($permitChildOnly) {
            return [];
        }
        if (!in_array($unitType, self::UNIT_TYPES_CHILDREN)) {
            return [];
        }
        return $adultRates;
    }

    /**
     * Validate product Id format aswell channel id from prodcut id is the same as channel being used throught Octo authentication
     * @param string $productId
     * @param string $authChannel
     * @throws InvalidProductIdException
     * @return bool
     */
    public function validateProductId(string $productId, string $authChannel): bool
    {
        if (!preg_match(self::PRODUCT_ID_REGEX, $productId)) {
            throw new InvalidProductIdException($productId);
        }

        $productIdChannel = explode(self::PRODUCT_ID_PIPE_SEPARATOR, $productId)[1];
        if ($productIdChannel !== $authChannel) {
            throw new InvalidProductIdException($productId);
        }

        return true;
    }

    public function parseProductId(string $productId): object
    {
        $apiCallParameters = new stdClass();
        $splitProductId = preg_split(self::PRODUCT_ID_SEPARATOR_REGEX, $productId);
        $apiCallParameters->tourId = $splitProductId[2];
        $apiCallParameters->channelId = $splitProductId[3];
        return $apiCallParameters;
    }

    public function isTourValidForProductList(SimpleXMLElement $tour): bool
    {
        $id = $this->buildProductId($tour);
        // Invalid timezone
        if (!isset($tour->start_timezone) && !isset($tour->end_timezone) && !isset($tour->account_timezone)) {
            $this->logInfo("skipped product {$id}: missing timeZone field.");
            return false;
        }
        // Invalid delivery formats
        if (!$this->areTourDeliveryFormatsValidForProductList($tour)) {
            return false;
        }
        // Invalid delivery methods
        if (!$this->areTourDeliveryMethodsValidForProductList($tour)) {
            return false;
        }
        // Invalid redemption method
        if (isset($tour->redemption_method) && !empty($tour->redemption_method)) {
            if (!in_array($tour->redemption_method, self::REDEMPTION_METHODS)) {
                $redemptionMethod = (string) $tour->redemption_method;
                $this->logInfo("skipped product {$id}: invalid redemption method: {$redemptionMethod}.");
                return false;
            }
        }
        // Invalid tour mapping
        if (!isset($tour->tour_departure_structure->type) || $tour->tour_departure_structure->type == self::MAPPING_STRUCTURE_TYPE_NOTSET) {
            $this->logInfo("skipped product {$id}: the tour mapping is missing or is not set.");
            return false;
        }
        return true;
    }

    public function buildProductId(SimpleXMLElement $tour): string
    {
        return "{$tour->distribution_identifier}|{$tour->channel_id}";
    }

    protected function getPricingPerFromTourXML(SimpleXMLElement $tour): string
    {
        return Product::PRICING_PER_UNIT;
    }

    protected function findTourDataFromAPI(string $productId): SimpleXMLElement
    {
        $apiCallParameters = $this->parseProductId($productId);
        try {
            $apiResponse = $this->tourCMSService->showTour($apiCallParameters->tourId, $apiCallParameters->channelId, cached: true);
        } catch (NoMatchingDataException $e) {
            throw new InvalidProductIdException($productId);
        }
        $tour = $apiResponse->tour;
        return $tour;
    }

    protected function getProductLocale(): string
    {
        $showChannelXML = $this->tourCMSService->showChannel();
        $language = !empty($showChannelXML->channel->lang) ? (string) $showChannelXML->channel->lang : self::DEFAULT_CHANNEL_LANG;
        $countryCode = !empty($showChannelXML->channel->address_country) ? (string) $showChannelXML->channel->address_country : self::DEFAULT_CHANNEL_COUNTRY;

        return "{$language}-{$countryCode}";
    }

    public function getProductTimeZone(SimpleXMLElement $tour): string
    {

        if (!empty($tour->start_timezone) && (string) $tour->start_timezone !== self::TIMEZONE_NOT_SET) {
            return (string) $tour->start_timezone;
        } 
        
        if (!empty($tour->end_timezone) && (string) $tour->end_timezone !== self::TIMEZONE_NOT_SET) {
            return (string) $tour->end_timezone;
        }

        if (!empty($tour->account_timezone) && (string) $tour->account_timezone !== self::TIMEZONE_NOT_SET) {
            return (string) $tour->account_timezone;
        }
            
        $this->errors[] = self::ERROR_TIMEZONE_MISSING;
        
        return "";
    }

    protected function getProductAvailabilityType(SimpleXMLElement $tour): string
    {
        $availabilityType = "";
        if (isset($tour->time_type)) {
            if ($tour->time_type == self::TIME_TYPE_STRICT || $tour->time_type == self::TIME_TYPE_STRICT_START) {
                $availabilityType = self::AVAILABILITY_TYPE_START_TIME;
            } else if ($this->isOpeningHours($tour)) {
                $availabilityType = self::AVAILABILITY_TYPE_OPENING_HOURS;
            }
        } else {
            $this->info[] = self::ERROR_AVAILABILITY_TYPE_MISSING;
        }
        return $availabilityType;
    }

    protected function getProductDeliveryFormats(SimpleXMLElement $tour): array
    {
        $deliveryFormats = [];

        if (!isset($tour->delivery_formats) || empty($tour->delivery_formats)) {
            return [self::DELIVERY_FORMAT_QRCODE];
        }
        
        $deliveryFormatsFromXML = XMLService::getArrayFromXmlNode($tour->delivery_formats, 'delivery_format');
        if (count($deliveryFormatsFromXML) <= 0) {
            return [self::DELIVERY_FORMAT_QRCODE];
        }

        $tourValidDeliveryFormats = array_intersect($deliveryFormatsFromXML, array_keys(self::DELIVERY_FORMATS));
        if (empty($tourValidDeliveryFormats)) {
            foreach ($deliveryFormatsFromXML as $deliveryFormat) {
                $this->errors[] = "invalid delivery format: {$deliveryFormat}";
                return [];
            }
        }

        foreach ($tourValidDeliveryFormats as $deliveryFormat) {
            $deliveryFormats[] = self::DELIVERY_FORMATS[(string) $deliveryFormat];
        }
        return $deliveryFormats;
    }

    protected function getProductDeliveryMethods(SimpleXMLElement $tour): array
    {
        $deliveryMethods = [];

        if (!isset($tour->delivery_methods) || empty($tour->delivery_methods)) {
            return [self::DELIVERY_METHOD_VOUCHER];
        }

        $deliveryMethodsFromXML = XMLService::getArrayFromXmlNode($tour->delivery_methods, 'delivery_method');
        if (count($deliveryMethodsFromXML) <= 0) {
            return [self::DELIVERY_METHOD_VOUCHER];
        }

        foreach ($deliveryMethodsFromXML as $deliveryMethod) {
            if (in_array($deliveryMethod, self::TCMS_DELIVERY_METHODS)) {
                $deliveryMethods[] =  $this->getOctoDeliveryMethodFromTourCMS((string) $deliveryMethod);
            } else {
                $this->errors[] = "invalid delivery method: {$deliveryMethod}";
            }
        }
        return $deliveryMethods;
    }

    protected function getProductRedemptionMethod(SimpleXMLElement $tour): string
    {
        $redemptionMethod = self::REDEMPTION_METHOD_DIGITAL;
        if (isset($tour->redemption_method) && !empty($tour->redemption_method)) {
            if (in_array($tour->redemption_method, self::REDEMPTION_METHODS)) {
                $redemptionMethod = (string) $tour->redemption_method;
            } else {
                $this->errors[] = "invalid redemption method: {$tour->redemption_method}";
            }
        }
        return $redemptionMethod;
    }

    protected function getOptionRequiredContactFields(SimpleXMLElement $tour): array
    {
        $requiredFields = [];
        $parent = null;
        if (isset($tour->new_booking->required_fields) && !empty($tour->new_booking->required_fields)) {
            $parent = $tour->new_booking->required_fields;
        }
        if (is_null($parent) && isset($tour->required_fields) && !empty($tour->required_fields)) {
            $parent = $tour->required_fields;
        }
        if (is_null($parent)) {
            return $requiredFields;
        }
        $requiredFieldsFromXML = XMLService::getArrayFromXmlNode($parent, 'field');
        foreach ($requiredFieldsFromXML as $requiredField) {
            $requiredFieldScope = (string) $requiredField->scope;
            if ($requiredFieldScope == self::REQUIRED_FIELD_SCOPE_LEADPAX || $requiredFieldScope == self::REQUIRED_FIELD_SCOPE_ALLPAX) {
                if (array_key_exists((string) $requiredField->name, self::CONTACT_FIELDS)) {
                    $requiredFields[] = self::CONTACT_FIELDS[(string) $requiredField->name];
                }
            }
        }
        return $requiredFields;
    }

    protected function getUnitRequiredContactFields(SimpleXMLElement $tour): array
    {
        $requiredFields = [];
        $parent = null;
        if (isset($tour->new_booking->required_fields) && !empty($tour->new_booking->required_fields)) {
            $parent = $tour->new_booking->required_fields;
        }
        if (is_null($parent) && isset($tour->required_fields) && !empty($tour->required_fields)) {
            $parent = $tour->required_fields;
        }
        if (is_null($parent)) {
            return $requiredFields;
        }
        $requiredFieldsFromXML = XMLService::getArrayFromXmlNode($parent, 'field');
        foreach ($requiredFieldsFromXML as $requiredField) {
            $requiredFieldScope = (string) $requiredField->scope;
            if ($requiredFieldScope == self::REQUIRED_FIELD_SCOPE_OTHERPAX || $requiredFieldScope == self::REQUIRED_FIELD_SCOPE_ALLPAX) {
                if (array_key_exists((string) $requiredField->name, self::CONTACT_FIELDS)) {
                    $requiredFields[] = self::CONTACT_FIELDS[(string) $requiredField->name];
                }
            }
        }
        return $requiredFields;
    }

    protected function logError(string $errorMessage, ?string $logChannel = null, array $extraParams = []): void
    {
        $this->logger->error(["message" => $errorMessage, ...$extraParams]);
    }

    protected function logInfo(string $infoMessage, ?string $logChannel = null, array $extraParams = []): void
    {
        $this->logger->info(["message" => $infoMessage, ...$extraParams]);
    }

    protected function areTourDeliveryFormatsValidForProductList(SimpleXMLElement $tour): bool
    {
        $id = $this->buildProductId($tour);
        if (isset($tour->delivery_formats)) {
            $deliveryFormatsFromXML = XMLService::getArrayFromXmlNode($tour->delivery_formats, 'delivery_format');
            if (!empty($deliveryFormatsFromXML)) {
                $tourValidDeliveryFormats = array_intersect($deliveryFormatsFromXML, array_keys(self::DELIVERY_FORMATS));
                if (empty($tourValidDeliveryFormats)) {
                    $this->logInfo("skipped product {$id}: invalid delivery format: " . implode(", ", $deliveryFormatsFromXML));
                    return false;
                }
            }
        }
        return true;
    } 

    protected function areTourDeliveryMethodsValidForProductList(SimpleXMLElement $tour): bool
    {
        $id = $this->buildProductId($tour);
        if (isset($tour->delivery_methods)) {
            $deliveryMethodsFromXML = XMLService::getArrayFromXmlNode($tour->delivery_methods, 'delivery_method');
            if (!empty($deliveryMethodsFromXML)) {
                foreach ($deliveryMethodsFromXML as $deliveryMethod) {
                    if (!in_array($deliveryMethod, self::TCMS_DELIVERY_METHODS)) {
                        $this->logInfo("skipped product {$id}: invalid delivery method: {$deliveryMethod}.");
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @param string $productId in format (ACCOUNT TWO FIRST LETTERS)_(ACCOUNT_ID)_(TOUR_ID). e.g: TE_1_67|142
     * @return string
     */
    public function getTourDistributionIdentifierFromProductId(string $productId): string
    {
        return explode('|', $productId)[0];
    }

    /**
     * @param string $productId in format (ACCOUNT TWO FIRST LETTERS)_(ACCOUNT_ID)_(TOUR_ID). e.g: TE_1_67|142
     * @return string
     */
    public function getTourIdFromProductId(string $productId): string
    {
        $distributionIdentifier = explode('|', $productId)[0];
        $distributionIdentifierSplitted = explode('_', $distributionIdentifier);
        
        return $distributionIdentifierSplitted[2];
    }

    protected function getOctoDeliveryMethodFromTourCMS(string $tcmsDeliveryMethod): string
    {
        if ($tcmsDeliveryMethod == self::TCMS_DELIVERY_METHOD_TICKET) {
            return self::DELIVERY_METHOD_TICKET;
        }

        return self::DELIVERY_METHOD_VOUCHER;
    }

    // Content
    protected function getProductFeatures(SimpleXMLElement $tour): array
    {
        $features = [];

        foreach (self::FEATURES_TYPES as $type => $fieldName) {
            if (isset($tour->$fieldName) && !empty($tour->$fieldName)) {

                $shortDescription = (string) $tour->$fieldName;

                if (in_array($fieldName, self::CONTENT_FIELDS_TO_SPLIT) && $this->haveTextListCharacters($shortDescription)) {

                    $array = explode("\n", $shortDescription);

                    foreach ($array as $text) {

                        if ($this->textStartsWithListCharacters($text)) {
                            $text = str_replace(["* ", "• ", "- ", "· "], "", $text);
                        }

                        if (!empty($text)) {
                            $features[] = (object) [
                                'shortDescription' => $text,
                                'type' => $type
                            ];
                        }
                    }
                } else {
                    $features[] = (object) [
                        'shortDescription' => $shortDescription,
                        'type' => $type
                    ]; 
                }
            }
        }
        return $features;
    }

    protected function haveTextListCharacters(string $text): bool
    {
        return str_contains($text, '*') || 
            str_contains($text, '•') || 
            str_contains($text, '-') || 
            str_contains($text, '·');
    }

    protected function textStartsWithListCharacters(string $text): bool
    {
        return str_starts_with($text, "* ") || 
            str_starts_with($text, "• ") || 
            str_starts_with($text, "- ") || 
            str_starts_with($text, "· ");
    }

    protected function getProductMedia(SimpleXMLElement $tour): array
    {
        $media = [];
        if (empty($tour->images)) return $media;

        // Images
        $images = XMLService::getArrayFromXmlNode($tour->images, 'image');
        $imageRel = Media::REL_COVER;

        foreach ($images as $image) {
            $src = $this->getBestImageUrl($image);
            $imageObject = new Media((string) $src);
            $imageObject->setType(Media::getFileType($src));
            if (!empty($image->image_desc)) {
                $imageObject->setCaption((string) $image->image_desc);
            }
            $imageObject->setRel($imageRel);
            $media[] = $imageObject;
            $imageRel = Media::REL_GALLERY;
        }

        // Video
        if (isset($tour->videos) && !empty($tour->videos)) {
            $videos = XMLService::getArrayFromXmlNode($tour->videos, 'video');
            foreach ($videos as $video) {
                $src = (string) $video->video_url;
                $videoObject = new Media($src);
                $videoObject->setType(Media::VALID_VIDEO_TYPES[(string) $video->video_service]);
                $videoObject->setRel(Media::REL_GALLERY);
                $media[] = $videoObject;
            }
        }

        return $media;
    }

    protected function getProductLocations(SimpleXMLElement $tour): array
    {
        $locations = [];
        if (isset($tour->geocode_start_point)) {
            $location = new Location;
            $location->setTitle(!empty($tour->geocode_start_point->label) ? (string) $tour->geocode_start_point->label : null);
            $locationTypes = [Location::TYPE_START];
            if (isset($tour->geocode_start_point->loc_relation) && (int) $tour->geocode_start_point->loc_relation == 1) {
                $locationTypes[] = Location::TYPE_ADMISSION_INCLUDED;
            }
            $location->setTypes($locationTypes);
            
            $place = new Place;
            $place->setLatitude(explode(',', (string) $tour->geocode_start_point->geocode)[0]);
            $place->setLongitude(explode(',', (string) $tour->geocode_start_point->geocode)[1]);
            
            $identifiers = $this->getLocationIdentifiers($tour->geocode_start_point);
            $place->setIdentifiers($identifiers);

            $location->setPlace($place);
            $locations[] = $location;
        }

        if (isset($tour->geocode_midpoints)) {
            $midpoints = XMLService::getArrayFromXmlNode($tour->geocode_midpoints, 'midpoint');
            
            foreach ($midpoints as $midpoint) {
                $location = new Location;
                $location->setTitle(!empty($midpoint->label) ? (string) $midpoint->label : null);
                $locationTypes = [Location::TYPE_ITINERARY_ITEM];
                if (isset($midpoint->loc_relation) && (int) $midpoint->loc_relation == 1) {
                    $locationTypes[] = Location::TYPE_ADMISSION_INCLUDED;
                }
                $location->setTypes($locationTypes);
                
                $place = new Place;
                $place->setLatitude(explode(',', (string) $midpoint->geocode)[0]);
                $place->setLongitude(explode(',', (string) $midpoint->geocode)[1]);
                
                $identifiers = $this->getLocationIdentifiers($midpoint);
                $place->setIdentifiers($identifiers);

                $location->setPlace($place);
                $locations[] = $location;
            }
            
        }

        if (isset($tour->geocode_end_point)) {
            $location = new Location;
            $location->setTitle(!empty($tour->geocode_end_point->label) ? (string) $tour->geocode_end_point->label : null);
            $locationTypes = [Location::TYPE_END];
            if (isset($tour->geocode_end_point->loc_relation) && (int) $tour->geocode_end_point->loc_relation == 1) {
                $locationTypes[] = Location::TYPE_ADMISSION_INCLUDED;
            }
            $location->setTypes($locationTypes);
            
            $place = new Place;
            $place->setLatitude(explode(',', (string) $tour->geocode_end_point->geocode)[0]);
            $place->setLongitude(explode(',', (string) $tour->geocode_end_point->geocode)[1]);
            
            $identifiers = $this->getLocationIdentifiers($tour->geocode_end_point);
            $place->setIdentifiers($identifiers);

            $location->setPlace($place);
            $locations[] = $location;
        }

        return $locations;
    }

    protected function getLocationIdentifiers(SimpleXMLElement $geocode): array
    {
        $identifiers = [];
        if (isset($geocode->google_place_id) && !empty($geocode->google_place_id)) {
            $identifiers[] = (object) [
                self::IDENTIFIER_TYPE_GOOGLE_PLACE_ID => (string) $geocode->google_place_id
            ];
        }
        return $identifiers;
    }

    protected function getDurationMinutesFrom(SimpleXMLElement $tour): int
    {
        if (!isset($tour->start_time) || !isset($tour->end_time)) {
            return self::DEFAULT_DURATION_MINUTES;
        }

        return (strtotime((string) $tour->end_time) - strtotime((string) $tour->start_time)) / 60;
    }

    protected function getBestImageUrl(SimpleXMLElement $image): string
    {
        if (isset($image->url_xlarge) && !empty($image->url_xlarge)) {
            return trim((string) $image->url_xlarge);
        }

        if (isset($image->url_large)) {
            return trim((string) $image->url_large);
        }
        
        return trim((string) $image->url);
    }

    protected function clearInfoAndErrors(): void
    {
        $this->info = [];
        $this->errors = [];
    }

    protected function isOpeningHours(SimpleXMLElement $tour): bool
    {
        return ((string) $tour->time_type) === self::TIME_TYPE_OPENING_HOURS;
    }

    protected function isGroupPricing(SimpleXMLElement $tour): bool
    {
        return (int) $tour->quantity_rule === 1;
    }

    protected function isHotel(SimpleXMLElement $tour): bool
    {
        return (int) $tour->has_h === 1;
    }

    protected function isFreesale(SimpleXMLElement $tour): bool
    {
        return (int) $tour->has_f === 1;
    }

    protected function isVolumePricing(SimpleXMLElement $tour): bool
    {
        return (int) $tour->volume_pricing == 1;
    }
}