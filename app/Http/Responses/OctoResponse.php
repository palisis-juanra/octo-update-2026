<?php

namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OctoResponse {

    const FIELD_ERROR_CODE = 'errorCode';
    const FIELD_ERROR_MESSAGE = 'errorMessage';
    const ERROR_CODE_UNAUTHORIZED = 'UNAUTHORIZED';
    const ERROR_CODE_FORBIDDEN = 'FORBIDDEN';
    const ERROR_MESSAGE_UNAUTHORIZED = 'Authorization Header not present';
    const ERROR_MESSAGE_FORBIDDEN = 'Unable to authenticate';

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
}