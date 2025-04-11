<?php

namespace App\Http\Responses;

use App\Facades\JSONLog;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OctoResponse {

    const FIELD_PRODUCT_ID = 'productId';
    const FIELD_OPTION_ID = 'optionId';
    const FIELD_UNIT_ID = 'unitId';
    const FIELD_ERROR_CODE = 'error';
    const FIELD_ERROR_MESSAGE = 'errorMessage';
    const FIELD_ERROR_LOG_ID = 'errorLogId';
    const FIELD_AVAILABILITY_ID = 'availabilityId';
    const FIELD_BOOKING_UUID = 'uuid';
    const ERROR_CODE_UNAUTHORIZED = 'UNAUTHORIZED';
    const ERROR_CODE_FORBIDDEN = 'FORBIDDEN';
    const ERROR_CODE_INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    const ERROR_CODE_NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
    const ERROR_CODE_INVALID_PRODUCT_ID = 'INVALID_PRODUCT_ID';
    const ERROR_CODE_INVALID_OPTION_ID = 'INVALID_OPTION_ID';
    const ERROR_CODE_INVALID_UNIT_ID = 'INVALID_UNIT_ID';
    const ERROR_CODE_BAD_REQUEST = 'BAD_REQUEST';
    const ERROR_CODE_INVALID_AVAILABILITY_ID = 'INVALID_AVAILABILITY_ID';
    const ERROR_CODE_INVALID_BOOKING_UUID = 'INVALID_BOOKING_UUID';
    const ERROR_CODE_UNPROCESSABLE_ENTITY = 'UNPROCESSABLE_ENTITY';
    const ERROR_MESSAGE_UNAUTHORIZED = 'Authorization Header not present';
    const ERROR_MESSAGE_FORBIDDEN = 'Unable to authenticate';
    const ERROR_MESSAGE_SERVER = 'There has been an error while processing the request, please try again later';
    const ERROR_MESSAGE_NOT_IMPLEMENTED = 'Endpoint not implemented.';
    const ERROR_MESSAGE_INVALID_PRODUCT_ID = 'Missing or invalid productId';
    const ERROR_MESSAGE_INVALID_OPTION_ID = 'Invalid OptionId. Must be a valid option in the scope of the product. e.g one of the following options: SINGLE, START_TIME, DEPARTURE_CODE|{CODE}, SUPPLIER_NOTE|{NOTE} or SUPPLIER_NOTE_PLUS_START_TIME|{NOTE}';
    const ERROR_MESSAGE_INVALID_UNIT_ID = 'Invalid UnitId. Must be a valid unit id in the scope of the product';
    const ERROR_MESSAGE_INVALID_AVAILABILITY_ID = 'Invalid AvailabilityId. Must be a valid one';
    const ERROR_MESSAGE_INVALID_BOOKING_UUID = 'The Booking UUID was invalid or missing';

    public static function UNAUTHORIZED(string $errorMessage = self::ERROR_MESSAGE_UNAUTHORIZED): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_UNAUTHORIZED,
            self::FIELD_ERROR_MESSAGE => $errorMessage
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public static function FORBIDDEN(string $errorMessage = self::ERROR_MESSAGE_FORBIDDEN): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_FORBIDDEN,
            self::FIELD_ERROR_MESSAGE => $errorMessage
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public static function INTERNAL_SERVER_ERROR(string $errorMessage = self::ERROR_MESSAGE_SERVER) : Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_INTERNAL_SERVER_ERROR,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_ERROR_LOG_ID => JSONLog::getLogId()
        ];

        return new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR); 
    }

    public static function OK(array | string $data): Response
    {
        return new JsonResponse($data, Response::HTTP_OK);
    }

    public static function INVALID_PRODUCT_ID(string $productId, string $errorMessage = self::ERROR_MESSAGE_INVALID_PRODUCT_ID): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_INVALID_PRODUCT_ID,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_PRODUCT_ID => $productId
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST); 
    }

    public static function INVALID_OPTION_ID(string $optionId, string $errorMessage = self::ERROR_MESSAGE_INVALID_OPTION_ID): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_INVALID_OPTION_ID,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_OPTION_ID => $optionId
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST); 
    }

    public static function INVALID_UNIT_ID(string $unitId, string $errorMessage = self::ERROR_MESSAGE_INVALID_UNIT_ID): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_INVALID_UNIT_ID,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_UNIT_ID => $unitId
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST); 
    }

    public static function BAD_REQUEST(string $errorMessage): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_BAD_REQUEST,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST);
    }

    public static function NOT_IMPLEMENTED(string $errorMessage = self::ERROR_MESSAGE_NOT_IMPLEMENTED): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_NOT_IMPLEMENTED,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
        ];

        return new JsonResponse($data, Response::HTTP_NOT_IMPLEMENTED);
    }

    public static function INVALID_AVAILABILITY_ID(string $availabilityId, $errorMessage = self::ERROR_MESSAGE_INVALID_AVAILABILITY_ID): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_INVALID_AVAILABILITY_ID,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_AVAILABILITY_ID => $availabilityId
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST);
    }

    public static function UNPROCESSABLE_ENTITY(string $errorMessage): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_UNPROCESSABLE_ENTITY,
            self::FIELD_ERROR_MESSAGE => $errorMessage 
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST);
    }

    public static function INVALID_BOOKING_UUID(string $bookingUuid, string $errorMessage = self::ERROR_MESSAGE_INVALID_BOOKING_UUID): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_INVALID_BOOKING_UUID,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_BOOKING_UUID => $bookingUuid
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST);  
    }
}