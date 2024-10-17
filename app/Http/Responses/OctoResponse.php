<?php

namespace App\Http\Responses;
use Symfony\Component\HttpFoundation\Response;

class OctoResponse {

    const FIELD_ERROR_CODE = 'errorCode';
    const FIELD_ERROR_MESSAGE = 'errorMessage';
    const ERROR_CODE_UNAUTHORIZED = 'UNAUTHORIZED';
    const ERROR_CODE_FORBIDDEN = 'FORBIDDEN';

    public static function UNAUTHORIZED(string $errorMessage = null): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_UNAUTHORIZED
        ];

        if (!empty($errorMessage)) {
            $data[self::FIELD_ERROR_MESSAGE] = $errorMessage;
        }

        return new Response(json_encode($data), Response::HTTP_UNAUTHORIZED);
    }

    public static function FORBIDDEN(string $errorMessage = null): Response
    {
        $data = [
            self::FIELD_ERROR_CODE => self::ERROR_CODE_FORBIDDEN,
        ];

        if (!empty($errorMessage)) {
            $data[self::FIELD_ERROR_MESSAGE] = $errorMessage;
        }

        return new Response(json_encode($data), Response::HTTP_FORBIDDEN);
    }
}