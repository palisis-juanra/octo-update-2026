<?php

namespace App\Services;

use App\Exceptions\AvailabilityRequestMissingParamException;
use App\Exceptions\AvailabilityRequestInvalidParamException;
use App\Http\Middleware\OctoAuthentication;
use App\Models\Availability\AvailabilityRequest;
use App\Interfaces\BaseAvailabilityRequest;
use App\Models\Availability\Pricing\MultiDayPricingAvailabilityRequest;
use App\Models\Availability\Pricing\SingleDayPricingAvailabilityRequest;
use App\Transformers\AvailabilityTransformer;
use App\Transformers\BaseTransformer;
use DateTime;

class AvailabilityService
{
    public TourCMSService $tourCMSService;
    public ProductService $productService;
    public OptionService $optionService;
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

    public function __construct(
        TourCMSService $tourCMSService, 
        ProductService $productService, 
        OptionService $optionService)
    {
        $this->tourCMSService = $tourCMSService;
        $this->productService = $productService;
        $this->optionService = $optionService;
        $this->transformer = new AvailabilityTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function getAvailabilities(BaseAvailabilityRequest $availabilityRequest): array
    {
        $departures = $availabilityRequest->getAvailabilities($this->tourCMSService);

        return $departures;
    }


    public function getAvailabilitiesTransformed(array $availabilities): array
    {
        $availabilitiesData = [];
        foreach ($availabilities as $availability) {
            $availabilitiesData[] = $this->transformer->transform($availability);
        }

        return $availabilitiesData;
    }

    public function getAvailabilityRequest(array $requestParams, string $octoCapabilities): BaseAvailabilityRequest
    {

        $productId = $requestParams[self::PARAM_PRODUCT_ID] ?? '';
        $tourId = $this->productService->getTourIdFromProductId($productId) ?? '';
        $optionId = $requestParams[self::PARAM_OPTION_ID] ?? '';
        $localDate = $requestParams[self::PARAM_LOCAL_DATE] ?? '';
        $localDateStart = $requestParams[self::PARAM_LOCAL_DATE_START] ?? '';
        $localDateEnd = $requestParams[self::PARAM_LOCAL_DATE_END] ?? '';
        $currency = $requestParams[self::PARAM_CURRENCY] ?? '';
        $units = $requestParams[self::PARAM_UNITS] ?? [];

        if (!empty($octoCapabilities) && strtolower($octoCapabilities) === 'pricing') {
            
            if (!empty($localDateStart) && !empty($localDateEnd)) {
                return new MultiDayPricingAvailabilityRequest($tourId, $optionId, $localDateStart, $localDateEnd, $currency);
            }

            return new SingleDayPricingAvailabilityRequest($tourId, $optionId, $localDateStart, $units, $currency);
        }
        
        if (!empty($localDateStart) && !empty($localDateEnd)) {
            return new AvailabilityRequest($tourId, $optionId, $localDateStart, $localDateEnd);
        }

        return new AvailabilityRequest($tourId, $optionId, $localDate);

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
            if ($this->validateDate($localDate) === false) {
                throw new AvailabilityRequestInvalidParamException('localDate must be a valid date in format YYYY-MM-DD');
            }

            return;
        }
        
        $localDateStart = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_START];

        if ($this->validateDate($localDateStart) === false) {
            throw new AvailabilityRequestInvalidParamException('localDateStart must be a valid date in format YYYY-MM-DD');
        }

        $localDateEnd = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_END] ?? '';
        if (!empty($localeDateEnd) && $this->validateDate($localDateEnd) === false) {
            throw new AvailabilityRequestInvalidParamException('localDateEnd must be a valid date in format YYYY-MM-DD');
        }

    }

    public function validateDate($date, $format = 'Y-m-d'): bool
    {
        $dateTime = DateTime::createFromFormat($format, $date);

        return $dateTime && strtolower($dateTime->format($format)) === strtolower($date);
    }

}