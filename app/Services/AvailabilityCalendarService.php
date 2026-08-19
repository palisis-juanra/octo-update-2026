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
        self::PARAM_LOCAL_DATE_END,
    ];

    const DATE_FORMAT = 'Y-m-d';

    const ERROR_MESSAGE_LOCAL_DATE_START_INVALID = 'localDateStart must be a valid date in format YYYY-MM-DD';

    const ERROR_MESSAGE_LOCAL_DATE_END_INVALID = 'localDateEnd must be a valid date in format YYYY-MM-DD';

    const ERROR_MESSAGE_LOCAL_DATE_INVALID_ORDER = 'localDateEnd must not be earlier than localDateStart';

    const ERROR_MESSAGE_EMPTY_REQUIRED_PARAM = 'Empty required param';

    const ERROR_MESSAGE_MISSING_REQUIRED_PARAMS = 'Missing Required Params: ';

    public TourCMSService $tourCMSService;

    public ProductService $productService;

    public OptionService $optionService;

    public CalendarAvailabilityTransformer $transformer;

    public function __construct(TourCMSService $tourCMSService, ProductService $productService, OptionService $optionService, ?CalendarAvailabilityTransformer $transformer = null)
    {
        $this->tourCMSService = $tourCMSService;
        $this->productService = $productService;
        $this->optionService = $optionService;
        $this->transformer = $transformer ?? new CalendarAvailabilityTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function getCalendar(array $requestParams): array
    {
        $availabilityRequest = $this->getAvailabilityRequest($requestParams);
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
        $tourId = ! empty($productId) ? $this->productService->getTourIdFromProductId($productId) : '';
        $optionId = $requestParams[self::PARAM_OPTION_ID] ?? '';
        $localDateStart = $requestParams[self::PARAM_LOCAL_DATE_START] ?? '';
        $localDateEnd = $requestParams[self::PARAM_LOCAL_DATE_END] ?? '';
        $units = $requestParams[self::PARAM_UNITS] ?? [];

        return new AvailabilityCalendarRequest($tourId, $optionId, $localDateStart, $localDateEnd, $units);
    }

    public function validateRequestParams(array $requestParams): void
    {
        $missingParams = array_diff(self::REQUIRED_PARAMS, array_keys($requestParams));

        if (! empty($missingParams)) {
            throw new AvailabilityRequestMissingParamException(self::ERROR_MESSAGE_MISSING_REQUIRED_PARAMS.implode(', ', $missingParams));
        }

        foreach (self::REQUIRED_PARAMS as $param) {
            if (empty($requestParams[$param])) {
                throw new AvailabilityRequestMissingParamException(self::ERROR_MESSAGE_EMPTY_REQUIRED_PARAM.": {$param}");
            }
        }

        $localDateStart = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_START];

        if ($this->validateDate($localDateStart) === false) {
            throw new AvailabilityRequestInvalidParamException(self::ERROR_MESSAGE_LOCAL_DATE_START_INVALID);
        }

        $localDateEnd = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_END] ?? '';

        if ($this->validateDate($localDateEnd) === false) {
            throw new AvailabilityRequestInvalidParamException(self::ERROR_MESSAGE_LOCAL_DATE_END_INVALID);
        }

        if ($localDateStart > $localDateEnd) {
            throw new AvailabilityRequestInvalidParamException(self::ERROR_MESSAGE_LOCAL_DATE_INVALID_ORDER);
        }

        $this->productService->validateProductId($requestParams[AvailabilityCalendarService::PARAM_PRODUCT_ID], $requestParams[OctoAuthentication::FIELD_CHANNEL_ID]);

        $productId = $requestParams[self::PARAM_PRODUCT_ID];
        $product = $this->productService->find($productId);

        $optionId = $requestParams[self::PARAM_OPTION_ID];
        $this->optionService->validateOptionId($optionId);
        $option = $product->getOptionById($optionId);
    }

    public function validateDate($date, $format = self::DATE_FORMAT): bool
    {
        $dateTime = DateTime::createFromFormat($format, $date);

        return $dateTime && strtolower($dateTime->format($format)) === strtolower($date);
    }
}
