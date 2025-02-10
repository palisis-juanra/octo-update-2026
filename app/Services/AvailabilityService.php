<?php

namespace App\Services;

use App\Exceptions\AvailabilityRequestMissingParamException;
use App\Exceptions\AvailabilityRequestInvalidParamException;
use App\Exceptions\BadRequestException;
use App\Exceptions\InvalidAvailabilityIdException;
use App\Exceptions\InvalidBookingUUIDException;
use App\Features\Availability\AvailabilityRequest;
use App\Http\Middleware\OctoAuthentication;
use App\Interfaces\BaseAvailabilityRequest;
use App\Models\Availability\Availability;
use App\Transformers\AvailabilityTransformer;
use App\Transformers\BaseTransformer;
use DateInterval;
use DateTime;
use DateTimeZone;
use SimpleXMLElement;

class AvailabilityService
{
    public TourCMSService $tourCMSService;
    public ProductService $productService;
    public OptionService $optionService;
    public UnitService $unitService;
    public AvailabilityTransformer $transformer;

    const PARAM_PRODUCT_ID = 'productId';
    const PARAM_OPTION_ID = 'optionId';
    const PARAM_LOCAL_DATE = 'localDate';
    const PARAM_LOCAL_DATE_START = 'localDateStart';
    const PARAM_LOCAL_DATE_END = 'localDateEnd';
    const PARAM_AVAILABILITY_IDS = 'availabilityIds';
    const PARAM_CURRENCY = 'currency';
    const REQUIRED_PARAMS = [
        self::PARAM_PRODUCT_ID,
        self::PARAM_OPTION_ID
    ];

    const DEFAULT_START_TIME = '00:00';
    const DEFAULT_END_TIME = '23:59';

    const ERROR_MESSAGE_AVAILABILITY_NEED_DATE = 'Request body must have localDate param or localDateStart + localDateEnd param(s)';

    const TCMS_CUTOFF_BEFORE_START_SECONDS = 'before_start_sec';
    const TCMS_CUTOFF_DAY_BEFORE_TIME = 'day_before_time';
    const TCMS_CUTOFF_SAME_DAY_TIME = 'same_day_time';
    const CUTOFF_FORMAT = 'Y-m-d\TH:i:s\Z';
    const AVAILABILITY_ID_REGEX = '/^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])\|\d+$/';

    public function __construct(
        TourCMSService $tourCMSService, 
        ProductService $productService, 
        OptionService $optionService,
        UnitService $unitService)
    {
        $this->tourCMSService = $tourCMSService;
        $this->productService = $productService;
        $this->optionService = $optionService;
        $this->unitService = $unitService;
        $this->transformer = new AvailabilityTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function getAvailabilities(BaseAvailabilityRequest $availabilityRequest): array
    {
        return $availabilityRequest->getAvailabilities($this->tourCMSService);
    }

    public function getAvailabilitiesTransformed(array $availabilities): array
    {
        $availabilitiesData = [];
        foreach ($availabilities as $availability) {
            $availabilitiesData[] = $this->transformer->transform($availability);
        }

        return $availabilitiesData;
    }

    /**
     * Summary of validateRequestParams
     * @param array $requestParams
     * @throws \App\Exceptions\AvailabilityRequestMissingParamException
     * @throws \App\Exceptions\AvailabilityRequestInvalidParamException
     * @return void
     */
    public function validateRequestParams(array $requestParams): void
    {
        $this->checkRequiredParams($requestParams);

        $this->productService->validateProductId($requestParams[AvailabilityService::PARAM_PRODUCT_ID], $requestParams[OctoAuthentication::FIELD_CHANNEL_ID]);
        
        $optionId = $requestParams[self::PARAM_OPTION_ID];
        if (!empty($optionId)) {
            $this->optionService->validateOptionId($optionId);
        }

        $localDate = $requestParams[AvailabilityService::PARAM_LOCAL_DATE] ?? '';
        $localDateStart = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_START] ?? '';
        $localDateEnd = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_END] ?? '';
        $availabilityIds = $requestParams[AvailabilityService::PARAM_AVAILABILITY_IDS] ?? [];

        if (!empty($localDate)) {

            if (!empty($localDateStart) || !empty($localDateEnd) || !empty($availabilityIds)) {
                throw new BadRequestException("You must pass in one of the following combinations of parameters for this endpoint: localDate / localeDateStart and localDateEnd / availabilityIds");
            }

            if (DateTimeService::validateDate($localDate) === false) {
                throw new AvailabilityRequestInvalidParamException('localDate must be a valid date in format YYYY-MM-DD');
            }
        } else if (!empty($availabilityIds)) {

            if (!empty($localDate) || !empty($localDateStart) || !empty($localDateEnd)) {
                throw new BadRequestException("You must pass in one of the following combinations of parameters for this endpoint: localDate / localeDateStart and localDateEnd / availabilityIds");
            }

            $this->validateAvailabilityIds($availabilityIds);

        } else {

            if (!empty($localDate) || !empty($availabilityIds)) {
                throw new BadRequestException("You must pass in one of the following combinations of parameters for this endpoint: localDate or localeDateStart and localDateEnd or availabilityIds");
            }
            
            if (empty($localDateStart) || empty($localDateEnd)) {
                throw new AvailabilityRequestInvalidParamException(self::ERROR_MESSAGE_AVAILABILITY_NEED_DATE);
            }

            if (DateTimeService::validateDate($localDateStart) === false) {
                throw new AvailabilityRequestInvalidParamException('localDateStart must be a valid date in format YYYY-MM-DD');
            }
    
            if (!empty($localeDateEnd) && DateTimeService::validateDate($localDateEnd) === false) {
                throw new AvailabilityRequestInvalidParamException('localDateEnd must be a valid date in format YYYY-MM-DD');
            }
        }
    
        $this->unitService->validateUnits($requestParams);

    }

    public function getCutoffFromTourCMSCutoff(array $cutoffData, string $startDay): string
    {
        $type = (string) $cutoffData['type'];
        $value = (string) $cutoffData['value'];

        $startDate = new DateTime($startDay, new DateTimeZone('UTC'));

        if ($value == '0') {
            return $startDate->format(self::CUTOFF_FORMAT);
        }

        if ($type == self::TCMS_CUTOFF_BEFORE_START_SECONDS) {
            $interval = new DateInterval("PT{$value}S");
            $startDate->sub($interval);
            return $startDate->format(self::CUTOFF_FORMAT);
        }

        $valueSplitted = explode(':', $value);
        $hour = $valueSplitted[0] ? (int) $valueSplitted[0] : 0;
        $minutes = $valueSplitted[1] ? (int) $valueSplitted[1] : 0;

        if ($type == self::TCMS_CUTOFF_DAY_BEFORE_TIME) {
            $startDate->modify('-1 day');
            $startDate->setTime($hour, $minutes);
        }
        
        if ($type == self::TCMS_CUTOFF_SAME_DAY_TIME) {
            $startDate->setTime($hour, $minutes);
        }

        return $startDate->format(self::CUTOFF_FORMAT);
    }

    public function find(string $availabilityId, string $tourId, string $optionId): Availability
    {
        $this->validateAvailabilityIds([$availabilityId]);

        $date = explode('|', $availabilityId)[0];
        $mappingQueryString = OptionService::getMappingQueryString($optionId);

        $response = $this->tourCMSService->showTourDepartures($tourId,$date, extraParams: $mappingQueryString);

        $departures = $this->tourCMSService->getArrayFromXmlNode($response->tour->dates_and_prices, 'departure');

        $availabilityRequest = new AvailabilityRequest($tourId, $optionId, $date);
        $availability = $availabilityRequest->getAvailabilityFromDeparturesById($availabilityId, $departures);

        return $availability;
    }

    public function generateAvailabilityFromBookingXML(SimpleXMLElement $showBookingXML): Availability
    {
        $components = $this->tourCMSService->getArrayFromXmlNode($showBookingXML->booking->components, 'component');
        $component = $components[0];
        return $this->generateAvailabilityObjectFromComponent($component);
    }

    public function generateAvailabilityObjectFromComponent(SimpleXMLElement $component): Availability
    {
        $departureId = $this->getDepartureIdFromAPIResponse($component);
        list($startTimeHours, $startTimeMinutes) = explode(":", !empty($component->start_time) ? (string) $component->start_time : self::DEFAULT_START_TIME);
        list($endTimeHours, $endTimeMinutes) = explode(":", !empty($component->end_time) ? (string) $component->end_time : self::DEFAULT_END_TIME);

        $availability = new Availability();

        $availability->setId($this->generateAvailabilityIdFromComponent($component));
        $availability->setDepartureId($departureId);
        $availability->setLocalDateTimeStart(DateTimeService::getISODateTimeString((string) $component->start_date, $startTimeHours, $startTimeMinutes));
        $availability->setLocalDateTimeEnd(DateTimeService::getISODateTimeString((string) $component->end_date, $endTimeHours, $endTimeMinutes));
        $availability->setAllDay((string) $component->availability_type === ProductService::AVAILABILITY_TYPE_OPENING_HOURS);
        $availability->setOpeningHoursFrom(!empty($component->start_time) ? (string) $component->start_time : self::DEFAULT_START_TIME);
        $availability->setOpeningHoursTo(!empty($component->end_time) ? (string) $component->end_time : self::DEFAULT_END_TIME);

        return $availability;
    }

    public function generateAvailabilityIdFromComponent(SimpleXMLElement $component): string
    {
        $departureId = (string)$component->date_id;
        $startDate = (string)$component->start_date;
        return "{$startDate}|{$departureId}";
    }

    public function validateAvailabilityId(string $availabilityId):bool
    {
        return !(empty($availabilityId) || !preg_match(self::AVAILABILITY_ID_REGEX, $availabilityId));
    }

    public function validateAvailabilityIds(array $availabilityIds): bool
    {
        foreach ($availabilityIds as $availabilityId) {
            if (!$this->validateAvailabilityId($availabilityId)) {
                throw new InvalidAvailabilityIdException($availabilityId);
            };
        }
        return true;
    }

    public function getAvailabilityObjectFromAvailabilityId(string $availabilityId): Availability
    {
        $availability = new Availability;
        $availability->setId($availabilityId);
        $startDate = (string)explode('|', $availabilityId)[0];
        $departureId = (int)explode('|', $availabilityId)[1];
        $availability->setDate($startDate);
        $availability->setDepartureId($departureId);
        return $availability;
    }

    protected function checkRequiredParams(array $requestParams):bool
    {
        $missingParams = array_diff(self::REQUIRED_PARAMS, array_keys($requestParams));
        if (!empty($missingParams)) {
            throw new AvailabilityRequestMissingParamException('Missing Required Params: ' . implode(', ', $missingParams));
        }

        foreach (self::REQUIRED_PARAMS as $param) {
            if (empty($requestParams[$param])) {
                throw new AvailabilityRequestMissingParamException('Empty required params: ' . $param);
            }
        }
        return true;
    }

    protected function getDepartureIdFromAPIResponse(SimpleXMLElement $apiResponse):int
    {
        // Get departureId depending API responses no consistent from TourCMS API. Sometimes field is date_id and others is departure_id
        $departureId = isset($apiResponse->date_id) ? (int) $apiResponse->date_id : 0;
        $departureId = $departureId == 0 && isset($apiResponse->departure_id) ? (int) $apiResponse->departure_id : $departureId;
        return $departureId;
    }


}