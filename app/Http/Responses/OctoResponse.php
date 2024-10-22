<?php

namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OctoResponse {

    const FIELD_ERROR_CODE = 'errorCode';
    const FIELD_ERROR_MESSAGE = 'errorMessage';
    const ERROR_CODE_UNAUTHORIZED = 'UNAUTHORIZED';
    const ERROR_CODE_FORBIDDEN = 'FORBIDDEN';
    const ERROR_CODE_INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    const ERROR_MESSAGE_UNAUTHORIZED = 'Authorization Header not present';
    const ERROR_MESSAGE_FORBIDDEN = 'Unable to authenticate';
    const ERROR_MESSAGE_SERVER = 'There have been an error while processing the request, please try again later';

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
}