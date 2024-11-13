<?php

namespace App\Services;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\InvalidProductContentException;
use App\Exceptions\NoMatchingDataException;
use App\Http\Middleware\OctoAuthentication;
use App\Models\Product;
use App\Transformers\BaseTransformer;
use App\Transformers\ProductTransformer;
use SimpleXMLObject;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use TourCMS\Utils\TourCMS;

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
    private $errors = [];

    public TourCMSService $tourCMSService;
    public ProductTransformer $productTransformer;
    public JSONLogService $logger;
    public LocaleService $localeService;
    public function __construct(Request $request, TourCMSService $tourCMSService)
    {
        $this->tourCMSService = $tourCMSService;
        $this->productTransformer = new ProductTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->logger = $this->loadLogger($request);
        $this->localeService = new LocaleService();
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
        $product = $this->createProductFromTourXML($tour);

        return $product;
    }

    public function createProductFromTourXML(\SimpleXMLElement $tour): Product
    {
        $id = "{$tour->distribution_identifier}|{$tour->channel_id}";
        $internalName = (string) $tour->tour_name;
        $reference = null;
        if (isset($tour->supplier_tour_code)) {
            $reference = $tour->supplier_tour_code;
        } else {
            $this->logger->info("reference field is missing");
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
            $options = $this->getProductOptions($tour, $tour->tour_departure_structure->type);
        } else {
            $this->manageError("the tour mapping is missing");
        }

        if (count($this->errors) != 0) {
            $errorString = implode(', ', $this->errors);
            throw new InvalidProductContentException("The content of the product is invalid: {$errorString}");
        }

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

        return $product;
    }

    protected function getProductOptions($tour, $structureType): array
    {
        $options = [];

        if ($structureType != self::MAPPING_STRUCTURE_TYPE_NOTSET) {
            $numOptions = 1;
            if ($structureType == self::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE || $structureType == self::MAPPING_STRUCTURE_TYPE_DEPARTURE_CODE || $structureType == self::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME) {
                if (isset($tour->tour_departure_structure->departure_types->type)) {
                    $numOptions = count($tour->tour_departure_structure->departure_types->type);
                }
            }
            for ($i = 0; $i < $numOptions; $i++) {
                $optionId = "";
                if ($numOptions == 1) {
                    $optionId = "{$tour->distribution_identifier}|{$structureType}";
                } else {
                    if (isset($tour->tour_departure_structure->departure_types->type->fields->field->value)) {
                        $mappingFieldValue = $tour->tour_departure_structure->departure_types->type[$i]->fields->field->value;
                        $optionId = "{$tour->distribution_identifier}|{$structureType}|{$mappingFieldValue}";
                    }
                }
                $optionDefault = $structureType == self::MAPPING_STRUCTURE_TYPE_SINGLE ? true : false;
                $optionInternalName = "";
                // As of now, we don't add supplier_tour_code since it is operators only.
                if (isset($tour->tour_name)) {
                    $optionInternalName = (string) $tour->tour_name;
                    if (isset($tour->supplier_tour_code)) {
                        $optionInternalName .= (string) $tour->supplier_tour_code;
                    } else {
                        $this->logger->info("option {$optionId} internalName field is missing");
                    }
                } else {
                    $this->logger->info("option {$optionId} internalName field is missing");
                }
                // As of now, always null since supplier_tour_code is operators only.
                $optionReference = null;
                if (isset($tour->supplier_tour_code)) {
                    $optionReference = (string) $tour->supplier_tour_code;
                } else {
                    $this->logger->info("option {$optionId} reference field is missing");
                }
                // TODO add self::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME case when fixed
                $optionAvailabilityLocalStartTimes = [];
                if ($structureType != self::MAPPING_STRUCTURE_TYPE_START_TIME && $structureType != self::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME) {
                    if (isset($tour->start_time) && !empty($tour->start_time) && $tour->start_time != self::START_TIME_MULTI) {
                        $optionAvailabilityLocalStartTimes[] = (string) $tour->start_time;
                    } else {
                        $optionAvailabilityLocalStartTimes[] = self::AVAILABILITY_LOCAL_START_TIMES_DEFAULT;
                    }
                } else if ($structureType == self::MAPPING_STRUCTURE_TYPE_START_TIME) {
                    if (isset($tour->tour_departure_structure->departure_types->type)) {
                        $departureTypesFromXML = $this->tourCMSService->getArrayFromXmlNode($tour->tour_departure_structure->departure_types, 'type');
                        foreach ($departureTypesFromXML as $type) {
                            if (isset($type->active) && $type->active == 1) {
                                if (isset($type->fields->field->value)) {
                                    $optionAvailabilityLocalStartTimes[] = (string) $type->fields->field->value;
                                }
                            }
                        }
                    }
                } else {
                    $this->logger->info("option {$optionId} availabilityLocalStartTimes fields are not present because of invalid mapping structure");
                }
                $optionCancellationCutoffUnit = self::CANCELLATION_CUTOFF_UNIT_DEFAULT;
                $optionCancellationCutoffAmount = self::CANCELLATION_CUTOFF_AMOUNT_DEFAULT;
                $optionCancellationCutoff = "{$optionCancellationCutoffAmount} {$optionCancellationCutoffUnit}s";
                if (isset($tour->cancellation_policy)) {
                    $cancellationPoliciesFromXML = $this->tourCMSService->getArrayFromXmlNode($tour->cancellation_policy, 'policy');
                    $policy = $cancellationPoliciesFromXML[0];
                    if ((isset($policy->type) && !empty($policy->type)) && (isset($policy->value) && !empty($policy->value))) {
                        $optionCancellationCutoffUnit = self::CANCELLATION_CUTOFF_UNITS[(string) $policy->type];
                        $optionCancellationCutoffAmount = (int) $policy->value;
                        $optionCancellationCutoff = "{$optionCancellationCutoffAmount} {$optionCancellationCutoffUnit}";
                        if ($optionCancellationCutoffAmount != 1) {
                            $optionCancellationCutoff .= "s";
                        }
                    } else {
                        $this->logger->info("option {$optionId} cutoff info is empty or missing");
                    }
                } else {
                    $this->logger->info("option {$optionId} cutoff field is missing");
                }
                $optionRequiredContactFields = $this->getOptionRequiredFields($tour);
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
                    'availabilityLocalStartTimes' => $optionAvailabilityLocalStartTimes,
                    'cancellationCutoff' => $optionCancellationCutoff,
                    'cancellationCutoffAmount' => $optionCancellationCutoffAmount,
                    'cancellationCutoffUnit' => $optionCancellationCutoffUnit,
                    'requiredContactFields' => $optionRequiredContactFields,
                    'restrictions' => $optionRestrictions,
                    'units' => $optionUnits
                ];
                $options[] = $option;
            }
        } else {
            $this->manageError("the tour departure structure is not set");
        }
        return $options;
    }

    public function getOptionUnits(\SimpleXMLElement $tour): array
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

        if (count($ratesFromXML) != 0) {
            foreach ($ratesFromXML as $rate) {
                $unitId = "{$tour->distribution_identifier}|{$tour->channel_id}|{$rate->rate_id}";
                $unitInternalName = "";
                if (isset($rate->label_1)) {
                    $unitInternalName = (string) $rate->label_1;
                } else {
                    $this->logger->info("unit {$unitId} internalName field is missing");
                }
                $unitReference = null;
                if (isset($rate->rate_code)) {
                    $unitReference = (string) $rate->rate_code;
                } else {
                    $this->logger->info("unit {$unitId} reference field is missing");
                }
                $unitType = "";
                if (isset($rate->agecat)) {
                    $agecat = (string) $rate->agecat;
                    if (array_key_exists($agecat, self::UNIT_TYPES)) {
                        $unitType = self::UNIT_TYPES[$agecat];
                    } else {
                        $this->logger->info("unit {$unitId} invalid type: {$agecat}");
                    }
                } else {
                    $this->logger->info("unit {$unitId} type field is missing");
                }
                $unitRequiredContactFields = $this->getUnitRequiredFields($tour);
                $unitRestrictions = new stdClass();
                $unitRestrictions->minAge = 1;
                if (isset($rate->agerange_min)) {
                    $unitRestrictions->minAge = (int) $rate->agerange_min;
                } else {
                    $this->logger->info("unit {$unitId} restrictions minAge field is missing");
                }
                $unitRestrictions->maxAge = 99;
                if (isset($rate->agerange_max)) {
                    $unitRestrictions->maxAge = (int) $rate->agerange_max;
                } else {
                    $this->logger->info("unit {$unitId} restrictions maxAge field is missing");
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
                $unitRestrictions->paxCount = 1;
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

    public function findTourDataFromAPI(string $productId): \SimpleXMLElement
    {
        $apiCallParameters = $this->parseProductId($productId);
        $apiResponse = $this->tourCMSService->showTour($apiCallParameters->tourId, $apiCallParameters->channelId);
        switch ((string) $apiResponse->error) {
            case self::API_RESPONSE_OK:
                $tour = $apiResponse->tour;
                break;
            case self::API_RESPONSE_NO_MATCHING_DATA:
                throw new NoMatchingDataException();
            default:
                throw new APICallNotOKException();
        }
        return $tour;
    }

    public function getTourListData(string $channelId): array
    {
        $apiResponse = $this->tourCMSService->listTours($channelId, "?".TourCMSService::LIST_TOURS_EXTENDED_TOUR_INFO_PARAM);
        if ($apiResponse->error == self::API_RESPONSE_OK) {
            $toursFromXML = $this->tourCMSService->getArrayFromXmlNode($apiResponse, 'tour');
        } else {
            if ($apiResponse->error == self::API_RESPONSE_NO_MATCHING_DATA) {
                throw new NoMatchingDataException();
            }
        }
        return $toursFromXML;
    }

    public function validateProductId(string $productId, string $authChannel): bool
    {
        if (!preg_match(self::PRODUCT_ID_REGEX, $productId)) {
            return false;
        }
        $productIdChannel = explode(self::PRODUCT_ID_PIPE_SEPARATOR, $productId)[1];
        return $productIdChannel === $authChannel;
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
        $id = "{$tour->distribution_identifier}|{$tour->channel_id}";
        // Invalid timezone
        if (!isset($tour->start_timezone) && !isset($tour->end_timezone) && !isset($tour->account_timezone)) {
            $this->logger->info("skipped product {$id}: missing timeZone field.");
            return false;
        }
        // Invalid delivery formats
        if (!isset($tour->delivery_formats)) {
            $this->logger->info("skipped product {$id}: delivery formats field is missing.");
            return false;
        }
        $deliveryFormatsFromXML = $this->tourCMSService->getArrayFromXmlNode($tour->delivery_formats, 'delivery_format');
        if (empty($deliveryFormatsFromXML)) {
            $this->logger->info("skipped product {$id}: delivery formats field is empty.");
            return false;
        }
        foreach ($deliveryFormatsFromXML as $deliveryFormat) {
            if (!in_array($deliveryFormat, self::DELIVERY_FORMATS)) {
                $this->logger->info("skipped product {$id}: invalid delivery format: {$deliveryFormat}.");
                return false;
            }
        }
        // Invalid delivery methods
        if (!isset($tour->delivery_methods)) {
            $this->logger->info("skipped product {$id}: delivery methods field is missing.");
            return false;
        }
        $deliveryMethodsFromXML = $this->tourCMSService->getArrayFromXmlNode($tour->delivery_methods, 'delivery_method');
        if (empty($deliveryMethodsFromXML)) {
            $this->logger->info("skipped product {$id}: delivery methods field is empty.");
            return false;
        }
        foreach ($deliveryMethodsFromXML as $deliveryMethod) {
            if (!in_array($deliveryMethod, self::DELIVERY_METHODS)) {
                $this->logger->info("skipped product {$id}: invalid delivery method: {$deliveryMethod}.");
                return false;
            }
        }
        // Invalid redemption method
        if (!isset($tour->redemption_method) || empty($tour->redemption_method)) {
            $this->logger->info("skipped product {$id}: redemption method field is missing or empty.");
            return false;
        }
        if (!in_array($tour->redemption_method, self::REDEMPTION_METHODS)) {
            $redemptionMethod = (string) $tour->redemption_method;
            $this->logger->info("skipped product {$id}: invalid redemption method: {$redemptionMethod}.");
            return false;
        }
        // Invalid tour mapping
        if (!isset($tour->tour_departure_structure->type) || $tour->tour_departure_structure->type == self::MAPPING_STRUCTURE_TYPE_NOTSET) {
            $this->logger->info("skipped product {$id}: the tour mapping is missing or is not set.");
            return false;
        }
        return true;
    }

    protected function getProductLocale(\SimpleXMLElement $tour): string
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
            $this->manageError("timeZone field is missing");
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
            $this->logger->info("availabilityType field is missing");
        }
        return $availabilityType;
    }

    protected function getProductDeliveryFormats(\SimpleXMLElement $tour): array
    {
        $deliveryFormats = [];
        if (isset($tour->delivery_formats)) {
            $deliveryFormatsFromXML = $this->tourCMSService->getArrayFromXmlNode($tour->delivery_formats, 'delivery_format');
            if (count($deliveryFormatsFromXML) != 0) {
                foreach ($deliveryFormatsFromXML as $deliveryFormat) {
                    if (array_key_exists((string) $deliveryFormat, self::DELIVERY_FORMATS)) {
                        $deliveryFormats[] = self::DELIVERY_FORMATS[(string) $deliveryFormat];
                    } else {
                        $this->manageError("invalid delivery format: {$deliveryFormat}");
                    }
                }
            } else {
                $this->manageError("delivery formats field is empty");
            }
        } else {
            $this->manageError("delivery formats field is missing");
        }
        return $deliveryFormats;
    }

    protected function getProductDeliveryMethods(\SimpleXMLElement $tour): array
    {
        $deliveryMethods = [];
        if (isset($tour->delivery_methods)) {
            $deliveryMethodsFromXML = $this->tourCMSService->getArrayFromXmlNode($tour->delivery_methods, 'delivery_method');
            if (count($deliveryMethodsFromXML) != 0) {
                foreach ($deliveryMethodsFromXML as $deliveryMethod) {
                    if (in_array($deliveryMethod, self::DELIVERY_METHODS)) {
                        $deliveryMethods[] = (string) $deliveryMethod;
                    } else {
                        $this->manageError("invalid delivery method: {$deliveryMethod}");
                    }
                }
            } else {
                $this->manageError("delivery methods field is empty");
            }
        } else {
            $this->manageError("delivery methods field is missing");
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
                $this->manageError("invalid redemption method: {$tour->redemption_method}");
            }
        } else {
            $this->manageError("redemption method field is missing or empty");
        }
        return $redemptionMethod;
    }

    protected function getOptionRequiredFields(\SimpleXMLElement $tour): array
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
                $requiredFields[] = (string) $requiredField->name;
            }
        }
        return $requiredFields;
    }

    protected function getUnitRequiredFields(\SimpleXMLElement $tour): array
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
                $requiredFields[] = (string) $requiredField->name;
            }
        }
        return $requiredFields;
    }

    protected function manageError(string $errorMessage): void
    {
        $this->logger->error($errorMessage);
        $this->errors[] = $errorMessage;
    }

    protected function loadLogger(Request $request)
    { 
        return new JSONLogService(
            $request->get(OctoAuthentication::FIELD_CHANNEL_ID),
            $request->get(OctoAuthentication::FIELD_MAID),
            self::ENDPOINT_NAME,
            $request->get(OctoAuthentication::FIELD_X_CORRELATION_ID),
            $request->get(OctoAuthentication::FIELD_X_REQUEST_ID)
        );
    }
}