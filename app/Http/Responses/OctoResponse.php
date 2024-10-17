<?php

namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OctoResponse {

    const FIELD_ERROR_CODE = 'errorCode';
    const FIELD_ERROR_MESSAGE = 'errorMessage';
    const ERROR_CODE_UNAUTHORIZED = 'UNAUTHORIZED';
    const ERROR_CODE_FORBIDDEN = 'FORBIDDEN';
    const ERROR_MESSAGE_UNAUTHORIZED = 'Authorization Header not present';
    const ERROR_MESSAGE_FORBIDDEN = 'Unable to authenticate';
    const HEADER_CONTENT_TYPE = 'Content-Type';
    const APPLICATION_JSON = 'application/json';
    const RESPONSE_HEADERS = [
        self::HEADER_CONTENT_TYPE => self::APPLICATION_JSON
    ];

    public static function UNAUTHORIZED(string $errorMessage = self::ERROR_MESSAGE_UNAUTHORIZED): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_UNAUTHORIZED,
            self::FIELD_ERROR_MESSAGE => $errorMessage
        ];

        return new Response(json_encode($data), Response::HTTP_UNAUTHORIZED, self::RESPONSE_HEADERS);
    }

    public static function FORBIDDEN(string $errorMessage = self::ERROR_MESSAGE_FORBIDDEN): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_FORBIDDEN,
            self::FIELD_ERROR_MESSAGE => $errorMessage
        ];

        return new Response(json_encode($data), Response::HTTP_FORBIDDEN, self::RESPONSE_HEADERS);
    }
}