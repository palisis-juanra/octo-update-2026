<?php

namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OctoResponse {

    const FIELD_PRODUCT_ID = 'productId';
    const FIELD_OPTION_ID = 'optionId';
    const FIELD_ERROR_CODE = 'errorCode';
    const FIELD_ERROR_MESSAGE = 'errorMessage';
    const ERROR_CODE_UNAUTHORIZED = 'UNAUTHORIZED';
    const ERROR_CODE_FORBIDDEN = 'FORBIDDEN';
    const ERROR_CODE_INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    const ERROR_CODE_INVALID_PRODUCT_ID = 'INVALID_PRODUCT_ID';
    const ERROR_MESSAGE_UNAUTHORIZED = 'Authorization Header not present';
    const ERROR_MESSAGE_FORBIDDEN = 'Unable to authenticate';
    const ERROR_MESSAGE_SERVER = 'There have been an error while processing the request, please try again later';
    const ERROR_MESSAGE_INVALID_PRODUCT_ID = 'Missing or invalid productId';
    const ERROR_MESSAGE_INVALID_OPTION_ID = 'Invalid OptionId. Must must one of the following options: SINGLE, START_TIME|{TIME}, DEPARTURE_CODE|{CODE}, SUPPLIER_NOTE|{NOTE} or SUPPLIER_NOTE_PLUS_START_TIME|{note and time}';

    public static function UNAUTHORIZED(string $errorMessage = self::ERROR_MESSAGE_UNAUTHORIZED): JsonResponse
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_UNAUTHORIZED,
            self::FIELD_ERROR_MESSAGE => $errorMessage
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public static function FORBIDDEN(string $errorMessage = self::ERROR_MESSAGE_FORBIDDEN): JsonResponse
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_FORBIDDEN,
            self::FIELD_ERROR_MESSAGE => $errorMessage
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public static function INTERNAL_SERVER_ERROR(string $errorMessage = self::ERROR_MESSAGE_SERVER) : JsonResponse
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_INTERNAL_SERVER_ERROR,
            self::FIELD_ERROR_MESSAGE => $errorMessage
        ];

        return new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR); 
    }

    public static function OK(array | string $data): JsonResponse
    {
        return new JsonResponse($data, Response::HTTP_OK);
    }

    public static function INVALID_PRODUCT_ID(string $productId, string $errorMessage = self::ERROR_MESSAGE_INVALID_PRODUCT_ID): JsonResponse
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_INVALID_PRODUCT_ID,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_PRODUCT_ID => $productId
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST); 
    }

    public static function INVALID_OPTION_ID(string $optionId, string $errorMessage = self::ERROR_MESSAGE_INVALID_OPTION_ID): JsonResponse
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_INTERNAL_SERVER_ERROR,
            self::FIELD_ERROR_MESSAGE => $errorMessage,
            self::FIELD_OPTION_ID => $optionId
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST); 
    }
}