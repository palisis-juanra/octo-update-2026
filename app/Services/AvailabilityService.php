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

            if (DateTimeService::validateDate($localDateStart) === false) {
                throw new AvailabilityRequestInvalidParamException('localDateStart must be a valid date in format YYYY-MM-DD');
            }
    
            $localDateEnd = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_END] ?? '';
            if (!empty($localeDateEnd) && DateTimeService::validateDate($localDateEnd) === false) {
                throw new AvailabilityRequestInvalidParamException('localDateEnd must be a valid date in format YYYY-MM-DD');
            }
        }
    
        $units = $requestParams[self::PARAM_UNITS] ?? [];
        if (!empty($units)) {
            $this->unitService->validateUnits($units);
        }

    }
}