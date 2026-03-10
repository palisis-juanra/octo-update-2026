<?php

namespace App\Http\Responses;

use App\Facades\JSONLog;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OctoResponse {

    public const string FIELD_ID = 'id';
    public const string FIELD_PRODUCT_ID = 'productId';
    public const string FIELD_OPTION_ID = 'optionId';
    public const string FIELD_UNIT_ID = 'unitId';
    public const string FIELD_ERROR = 'error';
    public const string FIELD_ERROR_CODE = 'errorCode';
    public const string FIELD_ERROR_MESSAGE = 'errorMessage';
    public const string FIELD_ERROR_LOG_ID = 'errorLogId';
    public const string FIELD_AVAILABILITY_ID = 'availabilityId';
    public const string FIELD_BOOKING_UUID = 'uuid';
    public const string FIELD_TIMESTAMP = 'timestamp';
    public const string FIELD_RATE_ID = 'rateId';

    public const string ERROR_CODE_UNAUTHORIZED = 'UNAUTHORIZED';
    public const string ERROR_CODE_FORBIDDEN = 'FORBIDDEN';
    public const string ERROR_CODE_INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    public const string ERROR_CODE_NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
    public const string ERROR_CODE_INVALID_PRODUCT_ID = 'INVALID_PRODUCT_ID';
    public const string ERROR_CODE_INVALID_OPTION_ID = 'INVALID_OPTION_ID';
    public const string ERROR_CODE_INVALID_UNIT_ID = 'INVALID_UNIT_ID';
    public const string ERROR_CODE_BAD_REQUEST = 'BAD_REQUEST';
    public const string ERROR_CODE_INVALID_AVAILABILITY_ID = 'INVALID_AVAILABILITY_ID';
    public const string ERROR_CODE_INVALID_BOOKING_UUID = 'INVALID_BOOKING_UUID';
    public const string ERROR_CODE_UNPROCESSABLE_ENTITY = 'UNPROCESSABLE_ENTITY';
    public const string ERROR_SUPPLIER_SUBSYSTEM_ERROR = 'SUPPLIER_SUBSYSTEM_ERROR';
    public const string ERROR_MESSAGE_UNAUTHORIZED = 'Authorization Header not present';
    public const string ERROR_MESSAGE_FORBIDDEN = 'Unable to authenticate';
    public const string ERROR_MESSAGE_SERVER = 'There has been an error while processing the request, please try again later';
    public const string ERROR_MESSAGE_NOT_IMPLEMENTED = 'Endpoint not implemented.';
    public const string ERROR_MESSAGE_INVALID_PRODUCT_ID = 'Missing or invalid productId';
    public const string ERROR_MESSAGE_INVALID_OPTION_ID = 'Invalid OptionId. Must be a valid option in the scope of the product. e.g one of the following options: SINGLE, START_TIME|{HH:MM}, DEPARTURE_CODE|{CODE}, SUPPLIER_NOTE|{NOTE} or SUPPLIER_NOTE_PLUS_START_TIME|{NOTE}';
    public const string ERROR_MESSAGE_INVALID_UNIT_ID = 'Invalid UnitId. Must be a valid unit id in the scope of the product';
    public const string ERROR_MESSAGE_INVALID_AVAILABILITY_ID = 'Invalid AvailabilityId. Must be a valid one';
    public const string ERROR_MESSAGE_INVALID_BOOKING_UUID = 'The Booking UUID was invalid or missing';
    public const string ERROR_SUPPLIER_SUBSYSTEM_ERROR_DEFAULT = 'Invalid subsystem response';
    public const string ERROR_MESSAGE_TOO_MANY_REQUESTS = 'Too many requests, please wait before continue';
    public const string ERROR_CODE_INVALID_RATE_ID = 'INVALID_RATE_ID';
    public const string ERROR_MESSAGE_INVALID_RATE_ID = "Invalid Rate Id, must be one of 'OPEN', 'GENIUS1' or 'GENIUS2'";

    public static function UNAUTHORIZED(string $errorMessage = self::ERROR_MESSAGE_UNAUTHORIZED): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_UNAUTHORIZED,
            self::FIELD_ERROR_MESSAGE => $errorMessage
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public static function FORBIDDEN(string $errorMessage = self::ERROR_MESSAGE_FORBIDDEN): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_FORBIDDEN,
            self::FIELD_ERROR_MESSAGE => $errorMessage
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public static function OK(array | string $data): Response
    {
        return new JsonResponse($data, Response::HTTP_OK);
    }

    public static function INVALID_PRODUCT_ID(string $productId, string $errorMessage = self::ERROR_MESSAGE_INVALID_PRODUCT_ID): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_INVALID_PRODUCT_ID,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_PRODUCT_ID => $productId
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST); 
    }

    public static function INVALID_OPTION_ID(string $optionId, string $errorMessage = self::ERROR_MESSAGE_INVALID_OPTION_ID): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_INVALID_OPTION_ID,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_OPTION_ID => $optionId
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST); 
    }

    public static function INVALID_UNIT_ID(string $unitId, string $errorMessage = self::ERROR_MESSAGE_INVALID_UNIT_ID): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_INVALID_UNIT_ID,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_UNIT_ID => $unitId
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST); 
    }

    public static function BAD_REQUEST(string $errorMessage): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_BAD_REQUEST,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST);
    }

    public static function NOT_IMPLEMENTED(string $errorMessage = self::ERROR_MESSAGE_NOT_IMPLEMENTED): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_NOT_IMPLEMENTED,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
        ];

        return new JsonResponse($data, Response::HTTP_NOT_IMPLEMENTED);
    }

    public static function INVALID_AVAILABILITY_ID(string $availabilityId, $errorMessage = self::ERROR_MESSAGE_INVALID_AVAILABILITY_ID): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_INVALID_AVAILABILITY_ID,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_AVAILABILITY_ID => $availabilityId
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST);
    }

    public static function UNPROCESSABLE_ENTITY(string $errorMessage): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_UNPROCESSABLE_ENTITY,
            self::FIELD_ERROR_MESSAGE => $errorMessage 
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST);
    }

    public static function INVALID_BOOKING_UUID(string $bookingUuid, string $errorMessage = self::ERROR_MESSAGE_INVALID_BOOKING_UUID): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_INVALID_BOOKING_UUID,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_BOOKING_UUID => $bookingUuid
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST);  
    }

    public static function INVALID_RATE_ID(string $rateId): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_INVALID_RATE_ID,
            self::FIELD_ERROR_MESSAGE => self::ERROR_MESSAGE_INVALID_RATE_ID,
            self::FIELD_RATE_ID => $rateId
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST);  
    }

    public static function TOO_MANY_DEPARTURES(): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_UNPROCESSABLE_ENTITY,
            self::FIELD_ERROR_MESSAGE => "Too many departures found. Please refine your search criteria.",
        ];

        return new JsonResponse($data, Response::HTTP_TOO_MANY_REQUESTS); 
    }

    // Error response, we add logId and timestamp in order to track/search logs
    public static function INTERNAL_SERVER_ERROR(string $errorMessage = self::ERROR_MESSAGE_SERVER) : Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_INTERNAL_SERVER_ERROR,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_ERROR_LOG_ID => JSONLog::getLogId(),
            self::FIELD_TIMESTAMP => time()
        ];

        return new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR); 
    }

    public static function SUBSYSTEM_ERROR(?string $errorMessage): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_UNPROCESSABLE_ENTITY,
            self::FIELD_ERROR_CODE => self::ERROR_SUPPLIER_SUBSYSTEM_ERROR,
            self::FIELD_ERROR_MESSAGE => $errorMessage ?? self::ERROR_SUPPLIER_SUBSYSTEM_ERROR_DEFAULT,
            self::FIELD_ERROR_LOG_ID => JSONLog::getLogId(),
            self::FIELD_TIMESTAMP => time()
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST);  
    }

    public static function TOO_MANY_REQUEST(): Response
    {
        $data = [
            self::FIELD_ERROR => self::ERROR_CODE_UNPROCESSABLE_ENTITY,
            self::FIELD_ERROR_MESSAGE => self::ERROR_MESSAGE_TOO_MANY_REQUESTS,
        ];

        return new JsonResponse($data, Response::HTTP_TOO_MANY_REQUESTS);
    }
}