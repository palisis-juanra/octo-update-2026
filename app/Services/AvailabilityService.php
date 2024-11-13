<?php

namespace App\Services;

use App\Exceptions\AvailabilityRequestMissingParamException;
use App\Exceptions\AvailabilityRequestInvalidParamException;
use App\Interfaces\AvailabilityRequestInterface;
use App\Models\MultiDayAvailabilityRequest;
use App\Models\SingleDayAvailabilityRequest;
use App\Transformers\DepartureTransformer;
use App\Transformers\BaseTransformer;
use DateTime;

class AvailabilityService
{
    public TourCMSService $tourCMSService;
    public ProductService $productService;
    public OptionService $optionService;
    public DepartureTransformer $transformer;

    const PARAM_PRODUCT_ID = 'productId';
    const PARAM_OPTION_ID = 'optionId';
    const PARAM_LOCAL_DATE_START = 'localDateStart';
    const PARAM_LOCAL_DATE_END = 'localDateEnd';
    const REQUIRED_PARAMS = [
        self::PARAM_PRODUCT_ID,
        self::PARAM_OPTION_ID,
        self::PARAM_LOCAL_DATE_START
    ];

    public function __construct(
        TourCMSService $tourCMSService, 
        ProductService $productService, 
        OptionService $optionService)
    {
        $this->tourCMSService = $tourCMSService;
        $this->productService = $productService;
        $this->optionService = $optionService;
        $this->transformer = new DepartureTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function getDepartures(AvailabilityRequestInterface $availabilityRequest): array
    {
        $departures = $availabilityRequest->getDepartures($this->tourCMSService);

        return $departures;
    }

    public function getDeparturesData(array $departures): array
    {
        $departuresData = [];
        foreach ($departures as $departure) {
            $departuresData[] = $this->transformer->transform($departure);
        }

        return $departuresData;
    }

    public function getAvailabilityRequest(array $requestParams): AvailabilityRequestInterface
    {
        $localDateEnd = $requestParams[self::PARAM_LOCAL_DATE_END];

        if (!empty($localDateEnd)) {
            return new MultiDayAvailabilityRequest($requestParams);
        }
        
        return new SingleDayAvailabilityRequest($requestParams);
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

        $this->productService->validateProductId($requestParams[AvailabilityService::PARAM_PRODUCT_ID]);
        
        
        $optionId = $requestParams[self::PARAM_OPTION_ID];
        if (!empty($optionId)) {
            $this->optionService->validateOptionId($optionId);
        }

        $localDateStart = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_START];
        $localDateEnd = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_END];

        if ($this->validateDate($localDateStart) === false) {
            throw new AvailabilityRequestInvalidParamException('localeDateStart must be a valid date in format YYYY-MM-DD');
        }

        if (!empty($localeDateEnd) && $this->validateDate($localDateEnd) === false) {
            throw new AvailabilityRequestInvalidParamException('localeDateEnd must be a valid date in format YYYY-MM-DD');
        }

    }

    public function validateDate($date, $format = 'Y-m-d'): bool
    {
        $dateTime = DateTime::createFromFormat($format, $date);

        return $dateTime && strtolower($dateTime->format($format)) === strtolower($date);
    }

    public function validateProductId(): void
    {

    }
}