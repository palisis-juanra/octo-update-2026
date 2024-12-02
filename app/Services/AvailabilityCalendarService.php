<?php

namespace App\Services;

use App\Exceptions\AvailabilityRequestInvalidParamException;
use App\Exceptions\AvailabilityRequestMissingParamException;
use App\Http\Middleware\OctoAuthentication;
use App\Models\Availability\AvailabilityCalendarRequest;
use App\Transformers\BaseTransformer;
use App\Transformers\CalendarAvailabilityTransformer;
use DateTime;

class AvailabilityCalendarService
{
    const PARAM_PRODUCT_ID = 'productId';
    const PARAM_OPTION_ID = 'optionId';
    const PARAM_LOCAL_DATE_START = 'localDateStart';
    const PARAM_LOCAL_DATE_END = 'localDateEnd';
    const PARAM_UNITS = 'units';
    const REQUIRED_PARAMS = [
        self::PARAM_PRODUCT_ID,
        self::PARAM_OPTION_ID,
        self::PARAM_LOCAL_DATE_START,
        self::PARAM_LOCAL_DATE_END
    ];

    public TourCMSService $tourCMSService;
    public ProductService $productService;
    public OptionService $optionService;
    public CalendarAvailabilityTransformer $transformer;

    public function __construct(TourCMSService $tourCMSService, ProductService $productService, OptionService $optionService)
    {
        $this->tourCMSService = $tourCMSService;
        $this->productService = $productService;
        $this->optionService = $optionService;
        $this->transformer = new CalendarAvailabilityTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function getCalendar(AvailabilityCalendarRequest $availabilityRequest): array
    {
        $calendar = $availabilityRequest->getAvailabilities($this->tourCMSService);
        return $calendar;
    }

    public function getAvailabilityCalendarTransformed(array $availabilities): array
    {
        $availabilitiesData = [];
        foreach ($availabilities as $availability) {
            $availabilitiesData[] = $this->transformer->transform($availability);
        }

        return $availabilitiesData;
    }

    public function getAvailabilityRequest(array $requestParams): AvailabilityCalendarRequest
    {

        $productId = $requestParams[self::PARAM_PRODUCT_ID] ?? '';
        $tourId = $this->productService->getTourIdFromProductId($productId) ?? '';
        $optionId = $requestParams[self::PARAM_OPTION_ID] ?? '';
        $localDateStart = $requestParams[self::PARAM_LOCAL_DATE_START] ?? '';
        $localDateEnd = $requestParams[self::PARAM_LOCAL_DATE_END] ?? '';
        $units = $requestParams[self::PARAM_UNITS] ?? [];

        return new AvailabilityCalendarRequest($tourId, $optionId, $localDateStart, $localDateEnd, $units);
    }

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

        $this->productService->validateProductId($requestParams[AvailabilityCalendarService::PARAM_PRODUCT_ID], $requestParams[OctoAuthentication::FIELD_CHANNEL_ID]);
        
        $optionId = $requestParams[self::PARAM_OPTION_ID];
        if (!empty($optionId)) {
            $this->optionService->validateOptionId($optionId);
        }
        
        $localDateStart = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_START];

        if ($this->validateDate($localDateStart) === false) {
            throw new AvailabilityRequestInvalidParamException('localDateStart must be a valid date in format YYYY-MM-DD');
        }

        $localDateEnd = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_END] ?? '';
        if ($this->validateDate($localDateEnd) === false) {
            throw new AvailabilityRequestInvalidParamException('localDateEnd must be a valid date in format YYYY-MM-DD');
        }
    }

    public function validateDate($date, $format = 'Y-m-d'): bool
    {
        $dateTime = DateTime::createFromFormat($format, $date);

        return $dateTime && strtolower($dateTime->format($format)) === strtolower($date);
    }

}