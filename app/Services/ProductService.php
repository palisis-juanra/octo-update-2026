<?php

namespace App\Services;

use App\Exceptions\InvalidProductContentException;
use App\Exceptions\InvalidProductIdException;
use App\Http\Middleware\OctoAuthentication;
use App\Models\Product;
use App\Transformers\BaseTransformer;
use App\Transformers\ProductTransformer;
use SimpleXMLElement;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

class ProductService
{
    public const ENDPOINT_NAME = 'products';
    const API_RESPONSE_OK = 'OK';
    const API_RESPONSE_NO_MATCHING_DATA = 'NO MATCHING DATA';
    const PRODUCT_ID_REGEX = '/^([A-Z]{2}_\d+_\d+\|\d+)$/';
    const PRODUCT_ID_SEPARATOR_REGEX = '/[\||_]/';
    const PRODUCT_ID_PIPE_SEPARATOR = '|';
    const PRODUCT_ID_UNDERSCORE_SEPARATOR = '_';
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
        'QR_CODE' => self::DELIVERY_FORMAT_QRCODE,
        'PDF_URL' => self::DELIVERY_FORMAT_PDF_URL
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
    const CUTOFF_TYPE_BEFORE_START_SEC = 'before_start_sec';
    const CUTOFF_TYPE_DAY_BEFORE_TIME = 'day_before_time';
    const CUTOFF_TYPE_SAME_DAY_TIME = 'same_day_time';
    const CANCELLATION_CUTOFF_UNIT_MINUTE = 'minute';
    const CANCELLATION_CUTOFF_UNIT_HOUR = 'hour';
    const CANCELLATION_CUTOFF_UNIT_DAY = 'day';
    const CANCELLATION_CUTOFF_UNITS = [
        'm' => self::CANCELLATION_CUTOFF_UNIT_MINUTE,
        'h' => self::CANCELLATION_CUTOFF_UNIT_HOUR,
        'd' => self::CANCELLATION_CUTOFF_UNIT_DAY
    ];
    const CANCELLATION_CUTOFF_UNIT_DEFAULT = self::CANCELLATION_CUTOFF_UNIT_MINUTE;
    const CANCELLATION_CUTOFF_AMOUNT_DEFAULT = 45;
    const AVAILABILITY_LOCAL_START_TIMES_DEFAULT = '00:00';
    const TIME_TYPE_STRICT = 'strict';
    const TIME_TYPE_STRICT_START = 'strict_start';
    const TIME_TYPE_OPENING_HOURS = 'opening_hours';
    const UNIT_TYPE_ADULT = 'ADULT';
    const UNIT_TYPE_YOUTH = 'YOUTH';
    const UNIT_TYPE_CHILD = 'CHILD';
    const UNIT_TYPE_INFANT = 'INFANT';
    const UNIT_TYPE_SENIOR = 'SENIOR';
    const UNIT_TYPES = [
        'a' => self::UNIT_TYPE_ADULT,
        'y' => self::UNIT_TYPE_YOUTH,
        'c' => self::UNIT_TYPE_CHILD,
        'i' => self::UNIT_TYPE_INFANT,
        's' => self::UNIT_TYPE_SENIOR
    ];
    const CONTACT_FIELD_FIRST_NAME = 'firstName';
    const CONTACT_FIELD_LAST_NAME = 'lastName';
    const CONTACT_FIELD_PHONE_NUMBER = 'phoneNumber';
    const CONTACT_FIELDS = [
        'firstname' => self::CONTACT_FIELD_FIRST_NAME,
        'surname' => self::CONTACT_FIELD_LAST_NAME,
        'tel_mobile' => self::CONTACT_FIELD_PHONE_NUMBER,
    ];
    const MAPPING_STRUCTURE_TYPE_NOTSET = 'NOTSET';
    const MAPPING_STRUCTURE_TYPE_SINGLE = 'SINGLE';
    const MAPPING_STRUCTURE_TYPE_START_TIME = 'START_TIME';
    const MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE = 'SUPPLIER_NOTE';
    const MAPPING_STRUCTURE_TYPE_DEPARTURE_CODE = 'DEPARTURE_CODE';
    const MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME = 'SUPPLIER_NOTE_PLUS_START_TIME';
    const START_TIME_MULTI = 'MULTI';
    const DAY_IN_SECONDS = 86400;
    const HOUR_IN_SECONDS = 3600;
    const MINUTE_IN_SECONDS = 60;
    const DATE_TIME_TOMORROW = 'tomorrow';
    const REQUIRED_FIELD_SCOPE_ALLPAX = 'allpax';
    const REQUIRED_FIELD_SCOPE_LEADPAX = 'leadpax';
    const REQUIRED_FIELD_SCOPE_OTHERPAX = 'otherpax';
    const LOCALE_CODE_DEFAULT = 'en-GB';
    const PAX_COUNT_DEFAULT = 1;
    const MAX_AGE_DEFAULT = 99;
    const MIN_AGE_DEFAULT = 1;
    const ERROR_AVAILABILITY_TYPE_MISSING = 'availabilityType field is missing';
    const ERROR_DELIVERY_FORMATS_MISSING = 'delivery formats field is missing';
    const ERROR_DELIVERY_FORMATS_EMPTY = 'delivery formats field is empty';
    const ERROR_DELIVERY_METHODS_MISSING = 'delivery methods field is missing';
    const ERROR_DELIVERY_METHODS_EMPTY = 'delivery methods field is empty';
    const ERROR_DEPARTURE_STRUCTURE_NOT_SET = 'the tour departure structure is not set';
    const ERROR_REDEMPTION_METHOD_MISSING_OR_EMPTY = 'redemption method field is missing or empty';
    const ERROR_REFERENCE_MISSING = 'reference field is missing';
    const ERROR_TIMEZONE_MISSING = 'timeZone field is missing';
    const ERROR_TOUR_MAPPING_MISSING = 'the tour mapping is missing';    
    public TourCMSService $tourCMSService;
    public ProductTransformer $productTransformer;
    public JSONLogService $logger;
    public LocaleService $localeService;

    private $errors = [];
    private $info = [];

    public function __construct(TourCMSService $tourCMSService, JSONLogService $logger, LocaleService $localeService)
    {
        $this->tourCMSService = $tourCMSService;
        $this->productTransformer = new ProductTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->logger = $logger;
        $this->localeService = $localeService;
    }

    public function getProductList(string $channelId): array
    {
        $tourList = $this->getTourListData($channelId);
        $productList = [];
            foreach ($tourList as $tour) {
                if ($this->isTourValidForProductList($tour)) {
                    $product = $this->createProductFromTourXML($tour);
                    $productList[] = $product;
                }                
            }
        return $productList;
    }

    public function find(string $productId): Product
    {
        $tour = $this->findTourDataFromAPI($productId);
        $this->logger->info(["message" => "Show tour response", "APIResponse" => $tour]);
        $product = $this->createProductFromTourXML($tour);

        return $product;
    }

    public function createProductFromTourXML(\SimpleXMLElement $tour): Product
    {
        $id = $this->buildProductId($tour);
        $internalName = (string) $tour->tour_name;
        $reference = null;
        if (isset($tour->supplier_tour_code)) {
            $reference = $tour->supplier_tour_code;
        } else {
            $this->info[] = self::ERROR_REFERENCE_MISSING;
        }
        $locale = $this->getProductLocale($tour);
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

        $options = [];
        if (isset($tour->tour_departure_structure->type)) {
            $options = $this->getProductOptions($tour);
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

        $product = new Product();

        $product->setId($id)
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
                ->setMinBookingSize((int) $tour->min_booking_size ?? 1);

        return $product;
    }

    public function getProductOptions(SimpleXMLElement $tour): array
    {

        $structureType = $tour->tour_departure_structure->type ? (string) $tour->tour_departure_structure->type : null;

        if (empty($structureType) || $structureType == self::MAPPING_STRUCTURE_TYPE_NOTSET) {
            $this->errors[] = self::ERROR_DEPARTURE_STRUCTURE_NOT_SET;
            return [];
        }

        $options = []; 
        $mappings = $this->getActiveMappingsFromTour($tour);

        foreach ($mappings as $option => $availabilityStartTimes) {

            $optionId = "{$structureType}";

            if (!in_array($structureType, [self::MAPPING_STRUCTURE_TYPE_SINGLE, self::MAPPING_STRUCTURE_TYPE_START_TIME])) {
                $optionId .= "|{$option}";
            }

            $optionDefault = $structureType == self::MAPPING_STRUCTURE_TYPE_SINGLE ? true : false;
            $optionInternalName = "";

            // As of now, we don't add supplier_tour_code since it is operators only.
            $optionInternalName = $tour->tour_name ? (string) $tour->tour_name : '';
            if (isset($tour->supplier_tour_code)) {
                $optionInternalName .= (string) $tour->supplier_tour_code;
            }

            if (empty($optionInternalName)){
                $this->info[] = "option {$optionId} internalName field is missing";
            }

            $optionReference = isset($tour->supplier_tour_code) ? (string) $tour->supplier_tour_code : null;
            if (empty($optionReference)){
                $this->info[] = "option {$optionId} reference field is missing";
            }

            if (empty($availabilityStartTimes)) {
                $this->info[] = "option {$optionId} availabilityLocalStartTimes fields are not present because of invalid mapping structure";
            }
            $optionCancellationCutoffUnit = self::CANCELLATION_CUTOFF_UNIT_DEFAULT;
            $optionCancellationCutoffAmount = self::CANCELLATION_CUTOFF_AMOUNT_DEFAULT;
            $optionCancellationCutoff = "{$optionCancellationCutoffAmount} {$optionCancellationCutoffUnit}s";
            if (isset($tour->cancellation_policy) && isset($tour->cancellation_policy->policy)) {
                $cancellationPoliciesFromXML = $this->tourCMSService->getArrayFromXmlNode($tour->cancellation_policy, 'policy');
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
        
            $option = (object) [
                'id' => $optionId,
                'default' => $optionDefault,
                'internalName' => $optionInternalName,
                'reference' => $optionReference,
                'availabilityLocalStartTimes' => $availabilityStartTimes,
                'cancellationCutoff' => $optionCancellationCutoff,
                'cancellationCutoffAmount' => $optionCancellationCutoffAmount,
                'cancellationCutoffUnit' => $optionCancellationCutoffUnit,
                'requiredContactFields' => $optionRequiredContactFields,
                'restrictions' => $optionRestrictions,
                'units' => $optionUnits
            ];
            $options[] = $option;
        }

        return $options;
    }

    public function getOptionUnits(SimpleXMLElement $tour): array
    {
        $optionUnits = [];
        $parent = null;
        if (isset($tour->new_booking->people_selection)) {
            $parent = $tour->new_booking->people_selection;
        } else if (isset($tour->people_selection)) {
            $parent = $tour->people_selection;
        } else if (is_null($parent)) {
            return $optionUnits;
        }
        $ratesFromXML = $this->tourCMSService->getArrayFromXmlNode($parent, 'rate');

        if (count($ratesFromXML) > 0) {
            foreach ($ratesFromXML as $rate) {
                $unitId = $this->buildUnitId($tour, $rate);
                $unitInternalName = "";
                if (isset($rate->label_1)) {
                    $unitInternalName = (string) $rate->label_1;
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
                $unitRestrictions = new stdClass();
                $unitRestrictions->minAge = self::MIN_AGE_DEFAULT;
                if (isset($rate->agerange_min)) {
                    $unitRestrictions->minAge = (int) $rate->agerange_min;
                } else {
                    $this->info[] = "unit {$unitId} restrictions minAge field is missing";
                }
                $unitRestrictions->maxAge = self::MAX_AGE_DEFAULT;
                if (isset($rate->agerange_max)) {
                    $unitRestrictions->maxAge = (int) $rate->agerange_max;
                } else {
                    $this->info[] = "unit {$unitId} restrictions maxAge field is missing";
                }
                $unitRestrictions->idRequired = false;
                $unitRestrictions->minQuantity = null;
                if (isset($rate->minimum)) {
                    $unitRestrictions->minQuantity = (int) $rate->minimum;
                }
                $unitRestrictions->maxQuantity = null;
                if (isset($rate->maximum)) {
                    $unitRestrictions->maxQuantity = (int) $rate->maximum;
                }
                $unitRestrictions->paxCount = self::PAX_COUNT_DEFAULT;
                // Currently unsupported, empty by default.
                $unitRestrictions->accompaniedBy = [];

                $unit = (object) [
                    'id' => $unitId,
                    'internalName' => $unitInternalName,
                    'reference' => $unitReference,
                    'type' => $unitType,
                    'requiredContactFields' => $unitRequiredContactFields,
                    'restrictions' => $unitRestrictions
                ];
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
        $toursFromXML = $this->tourCMSService->getArrayFromXmlNode($apiResponse, 'tour');
        return $toursFromXML;
    }

    /**
     * Validate product Id format aswell channel id from prodcut id is the same as channel being used throught Octo authentication
     * @param string $productId
     * @param string $authChannel
     * @throws \App\Exceptions\InvalidProductIdException
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

    public function isTourValidForProductList(\SimpleXMLElement $tour): bool
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
        if (!isset($tour->redemption_method) || empty($tour->redemption_method)) {
            $this->logInfo("skipped product {$id}: redemption method field is missing or empty.");
            return false;
        }
        if (!in_array($tour->redemption_method, self::REDEMPTION_METHODS)) {
            $redemptionMethod = (string) $tour->redemption_method;
            $this->logInfo("skipped product {$id}: invalid redemption method: {$redemptionMethod}.");
            return false;
        }
        // Invalid tour mapping
        if (!isset($tour->tour_departure_structure->type) || $tour->tour_departure_structure->type == self::MAPPING_STRUCTURE_TYPE_NOTSET) {
            $this->logInfo("skipped product {$id}: the tour mapping is missing or is not set.");
            return false;
        }
        return true;
    }

    public function buildProductId(\SimpleXMLElement $tour): string
    {
        return "{$tour->distribution_identifier}|{$tour->channel_id}";
    }
    
    public function buildUnitId(\SimpleXMLElement $tour, \SimpleXMLElement $rate): string
    {
        return "{$tour->distribution_identifier}|{$rate->rate_id}";
    }

    protected function findTourDataFromAPI(string $productId): \SimpleXMLElement
    {
        $apiCallParameters = $this->parseProductId($productId);
        $apiResponse = $this->tourCMSService->showTour($apiCallParameters->tourId, $apiCallParameters->channelId);
        $tour = $apiResponse->tour;
        return $tour;
    }

    protected function getProductLocale(SimpleXMLElement $tour): string
    {
        $defaultLocale = self::LOCALE_CODE_DEFAULT;
        $countries = [];
        $languages = [];
        if (isset($tour->languages_spoken) && !empty($tour->languages_spoken)) {
            $languages = explode(',', $tour->languages_spoken);
        }
        if (isset($tour->country) && !empty($tour->country)) {
            $generatedLocale = "";
            $countries = explode(',', $tour->country);
            foreach ($countries as $country) {
                foreach ($languages as $language) {
                    $generatedLocale = $this->localeService->countryCodeToLocale($country, $language);
                    if (is_string($generatedLocale) && !empty($generatedLocale)) {
                        return $generatedLocale;
                    }
                }
                $generatedLocale = $this->localeService->countryCodeToLocale($country);
                if (is_string($generatedLocale) && !empty($generatedLocale)) {
                    return $generatedLocale;
                }
            }
        }
        return $defaultLocale;
    }

    protected function getProductTimeZone(\SimpleXMLElement $tour): string
    {
        $timeZone = "";
        if (isset($tour->start_timezone)) {
            $timeZone = (string) $tour->start_timezone;
        } else if (isset($tour->end_timezone) || isset($tour->account_timezone)) {
            $timeZone = isset($tour->end_timezone) ? (string) $tour->end_timezone : (string) $tour->account_timezone;
        } else {
            $this->errors[] = self::ERROR_TIMEZONE_MISSING;
        }
        return $timeZone;
    }

    protected function getProductAvailabilityType(\SimpleXMLElement $tour): string
    {
        $availabilityType = "";
        if (isset($tour->time_type)) {
            if ($tour->time_type == self::TIME_TYPE_STRICT || $tour->time_type == self::TIME_TYPE_STRICT_START) {
                $availabilityType = self::AVAILABILITY_TYPE_START_TIME;
            } else if ($tour->time_type == self::TIME_TYPE_OPENING_HOURS) {
                $availabilityType = self::AVAILABILITY_TYPE_OPENING_HOURS;
            }
        } else {
            $this->info[] = self::ERROR_AVAILABILITY_TYPE_MISSING;
        }
        return $availabilityType;
    }

    protected function getProductDeliveryFormats(\SimpleXMLElement $tour): array
    {
        $deliveryFormats = [];
        if (!isset($tour->delivery_formats) || empty($tour->delivery_formats)) {
            $this->errors[] = self::ERROR_DELIVERY_FORMATS_MISSING;
            return $deliveryFormats;
        }

        $deliveryFormatsFromXML = $this->tourCMSService->getArrayFromXmlNode($tour->delivery_formats, 'delivery_format');
        if (count($deliveryFormatsFromXML) == 0) {
            $this->errors[] = self::ERROR_DELIVERY_FORMATS_EMPTY;
            return $deliveryFormats;
        }
        
        foreach ($deliveryFormatsFromXML as $deliveryFormat) {
            if (array_key_exists((string) $deliveryFormat, self::DELIVERY_FORMATS)) {
                $deliveryFormats[] = self::DELIVERY_FORMATS[(string) $deliveryFormat];
            } else {
                $this->errors[] = "invalid delivery format: {$deliveryFormat}";
            }
        }
        
        
        return $deliveryFormats;
    }

    protected function getProductDeliveryMethods(\SimpleXMLElement $tour): array
    {
        $deliveryMethods = [];
        if (isset($tour->delivery_methods) && !empty($tour->delivery_methods)) {
            $deliveryMethodsFromXML = $this->tourCMSService->getArrayFromXmlNode($tour->delivery_methods, 'delivery_method');
            if (count($deliveryMethodsFromXML) != 0) {
                foreach ($deliveryMethodsFromXML as $deliveryMethod) {
                    if (in_array($deliveryMethod, self::DELIVERY_METHODS)) {
                        $deliveryMethods[] = (string) $deliveryMethod;
                    } else {
                        $this->errors[] = "invalid delivery method: {$deliveryMethod}";
                    }
                }
            } else {
                $this->errors[] = self::ERROR_DELIVERY_METHODS_EMPTY;
            }
        } else {
            $this->errors[] = self::ERROR_DELIVERY_METHODS_MISSING;
        }
        return $deliveryMethods;
    }

    protected function getProductRedemptionMethod(\SimpleXMLElement $tour): string
    {
        $redemptionMethod = "";
        if (isset($tour->redemption_method) && !empty($tour->redemption_method)) {
            if (in_array($tour->redemption_method, self::REDEMPTION_METHODS)) {
                $redemptionMethod = (string) $tour->redemption_method;
            } else {
                $this->errors[] = "invalid redemption method: {$tour->redemption_method}";
            }
        } else {
            $this->errors[] = self::ERROR_REDEMPTION_METHOD_MISSING_OR_EMPTY;
        }
        return $redemptionMethod;
    }

    protected function getOptionRequiredContactFields(\SimpleXMLElement $tour): array
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
        $requiredFieldsFromXML = $this->tourCMSService->getArrayFromXmlNode($parent, 'field');
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

    protected function getUnitRequiredContactFields(\SimpleXMLElement $tour): array
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
        $requiredFieldsFromXML = $this->tourCMSService->getArrayFromXmlNode($parent, 'field');
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

    protected function areTourDeliveryFormatsValidForProductList(\SimpleXMLElement $tour): bool
    {
        $id = $this->buildProductId($tour);
        if (!isset($tour->delivery_formats)) {
            $this->logInfo("skipped product {$id}: delivery formats field is missing.");
            return false;
        }
        $deliveryFormatsFromXML = $this->tourCMSService->getArrayFromXmlNode($tour->delivery_formats, 'delivery_format');
        if (empty($deliveryFormatsFromXML)) {
            $this->logInfo("skipped product {$id}: delivery formats field is empty.");
            return false;
        }
        foreach ($deliveryFormatsFromXML as $deliveryFormat) {
            if (!array_key_exists((string) $deliveryFormat, self::DELIVERY_FORMATS)) {
                $this->logInfo("skipped product {$id}: invalid delivery format: {$deliveryFormat}.");
                return false;
            }
        }
        return true;
    } 

    protected function areTourDeliveryMethodsValidForProductList(\SimpleXMLElement $tour): bool
    {
        $id = $this->buildProductId($tour);
        if (!isset($tour->delivery_methods)) {
            $this->logInfo("skipped product {$id}: delivery methods field is missing.");
            return false;
        }
        $deliveryMethodsFromXML = $this->tourCMSService->getArrayFromXmlNode($tour->delivery_methods, 'delivery_method');
        if (empty($deliveryMethodsFromXML)) {
            $this->logInfo("skipped product {$id}: delivery methods field is empty.");
            return false;
        }
        foreach ($deliveryMethodsFromXML as $deliveryMethod) {
            if (!in_array($deliveryMethod, self::DELIVERY_METHODS)) {
                $this->logInfo("skipped product {$id}: invalid delivery method: {$deliveryMethod}.");
                return false;
            }
        }
        return true;
    }

    /**
     * Summary of getTourIdFromProductId
     * @param string $productId in format (ACCOUNT TWO FIRST LETTERS)_(ACCOUNT_ID)_(TOUR_ID). e.g: TE_1_67|142
     * @return string
     */
    public function getTourIdFromProductId(string $productId): string
    {
        $distributionIdentifier = explode('|', $productId)[0];
        $distributionIdentifierSplitted = explode('_', $distributionIdentifier);
        
        return $distributionIdentifierSplitted[2];
    }

    /**
     * @param SimpleXMLElement $tour Tour XML Node
     * Get all the active mappings based on its structure type
     * Each element in array contains option and availabilityStartTimes
     * @return array[]
     */
    public function getActiveMappingsFromTour(SimpleXMLElement $tour): array
    {
        $structureType = (string) $tour->tour_departure_structure->type;
        $types = $this->tourCMSService->getArrayFromXmlNode($tour->tour_departure_structure->departure_types, 'type');
        $mappings = [];

        switch ($structureType) {

            case self::MAPPING_STRUCTURE_TYPE_START_TIME:
                foreach ($types as $mapping) {
                    if (isset($mapping->active) && $mapping->active == 1) {
                        if (isset($mapping->fields->field->value)) {
                            // Mappings key is empty, because we dont add any option specific for start time mapping
                            $mappings[''][] = (string) $mapping->fields->field->value;
                        }
                    }
                }
                return $mappings;

            case self::MAPPING_STRUCTURE_TYPE_DEPARTURE_CODE:
            case self::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE:
                foreach ($types as $mapping) {
                    if (isset($mapping->active) && $mapping->active == 1) {
                        if (isset($mapping->fields->field->value)) {

                            if (isset($tour->start_time) && !empty($tour->start_time) && $tour->start_time != self::START_TIME_MULTI) {
                                $availabilityStartTime = (string) $tour->start_time;
                            } else {
                                $availabilityStartTime = self::AVAILABILITY_LOCAL_START_TIMES_DEFAULT;
                            }

                            $mappings[(string) $mapping->fields->field->value] = [$availabilityStartTime];
                        }
                    }
                }
                
                return $mappings;

            case self::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME:

                foreach ($types as $mapping) {

                    if ($mapping->active == 0) {
                        continue;
                    }

                    // Skip partials mappings
                    $partialMapping = $mapping->partial;
                    if (!empty($partialMapping) && (int) $partialMapping != 0) {
                        continue;
                    }
    
                    $mappingObject = [];
    
                    foreach ($mapping->fields->field as $field) {
                        
                        $fieldName = (string) $field->name;
                        $fieldValue = (string) $field->value;
    
                        if ($fieldName == 'supplier_note') {
                            $mappingObject['supplier_note'] = $fieldValue;
                                                  
                        }
    
                        if ($fieldName == 'start_time') {
                            $mappingObject['start_time'] = $fieldValue;
                        }
                    }
    
                    if (!array_key_exists($mappingObject['supplier_note'], $mappings)){
                        $mappings[$mappingObject['supplier_note']] = [];
                    }
    
                    if (!in_array($mappingObject['start_time'], $mappings[$mappingObject['supplier_note']])) {
                        $mappings[$mappingObject['supplier_note']][] = $mappingObject['start_time'];
                    }
                }
                return $mappings;
            
            default:

                if ((string) $tour->start_time == self::START_TIME_MULTI) {
                    $this->errors[] = 'Product has an invalid time configuration. Tour cannot be mapped as SINGLE and have multiple start times';
                    throw new InvalidProductContentException($this->buildProductId($tour), 'Product has an invalid time configuration');
                }

                return ['' => [(string) $tour->start_time]];

        }
        
    }

}