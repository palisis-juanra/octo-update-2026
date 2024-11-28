<?php

namespace App\Services;

use App\Exceptions\AvailabilityRequestMissingParamException;
use App\Exceptions\AvailabilityRequestInvalidParamException;
use App\Http\Middleware\OctoAuthentication;
use App\Features\Availability\AvailabilityRequest;
use App\Interfaces\BaseAvailabilityRequest;
use App\Features\Availability\Pricing\MultiDayPricingAvailabilityRequest;
use App\Features\Availability\Pricing\SingleDayPricingAvailabilityRequest;
use App\Transformers\AvailabilityTransformer;
use App\Transformers\BaseTransformer;
use DateInterval;
use DateTime;
use DateTimeZone;
use stdClass;

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
    const PARAM_UNITS = 'units';
    const PARAM_CURRENCY = 'currency';
    const REQUIRED_PARAMS = [
        self::PARAM_PRODUCT_ID,
        self::PARAM_OPTION_ID
    ];

    const ERROR_MESSAGE_AVAILABILITY_NEED_DATE = 'Request body must have localDate param or localDateStart + localDateEnd param(s)';

    const TCMS_CUTOFF_BEFORE_START_SECONDS = 'before_start_sec';
    const TCMS_CUTOFF_DAY_BEFORE_TIME = 'day_before_time';
    const TCMS_CUTOFF_SAME_DAY_TIME = 'same_day_time';
    const CUTOFF_FORMAT = 'Y-m-d\TH:i:s\Z';

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

        $missingParams = array_diff(self::REQUIRED_PARAMS, array_keys($requestParams));

        if (!empty($missingParams)) {
            throw new AvailabilityRequestMissingParamException('Missing Required Params: ' . implode(', ', $missingParams));
        }

        foreach (self::REQUIRED_PARAMS as $param) {
            if (empty($requestParams[$param])) {
                throw new AvailabilityRequestMissingParamException('Empty required params: ' . $param);
            }
        }

        $this->productService->validateProductId($requestParams[AvailabilityService::PARAM_PRODUCT_ID], $requestParams[OctoAuthentication::FIELD_CHANNEL_ID]);
        
        
        $optionId = $requestParams[self::PARAM_OPTION_ID];
        if (!empty($optionId)) {
            $this->optionService->validateOptionId($optionId);
        }

        $localDate = $requestParams[AvailabilityService::PARAM_LOCAL_DATE] ?? '';

        if (!empty($localDate)) {
            if (DateTimeService::validateDate($localDate) === false) {
                throw new AvailabilityRequestInvalidParamException('localDate must be a valid date in format YYYY-MM-DD');
            }
        } else {
            $localDateStart = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_START] ?? '';
            $localDateEnd = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_END] ?? '';
            
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
    
        $units = $requestParams[self::PARAM_UNITS] ?? [];
        if (!empty($units)) {
            $this->unitService->validateUnits($units);
        }

    }

    public function getCutoffFromTourCMSCutoff(array $cutoffData, string $startDay): string
    {
        $type = (string) $cutoffData['type'];
        $value = (string) $cutoffData['value'];

        $startDate = new DateTime($startDay, new DateTimeZone('UTC'));

        if ($value == '0') {
            return $startDate->format(DateTime::ATOM);
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
}