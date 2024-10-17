<?php

namespace App\Http\Responses;
use Symfony\Component\HttpFoundation\Response;

class OctoResponse {

    const ERROR_CODE = 'errorCode';
    const ERROR_MESSAGE = 'errorMessage';
    const ERROR_CODE_UNAUTHORIZED = 'UNAUTHORIZED';
    const ERROR_CODE_FORBIDDEN = 'FORBIDDEN';

    public static function UNAUTHORIZED(string $errorMessage = null): Response
    {
        $data = [
            self::ERROR_CODE => self::ERROR_CODE_UNAUTHORIZED
        ];

        if (!empty($errorMessage)) {
            $data[self::ERROR_MESSAGE] = $errorMessage;
        }

        return new Response(json_encode($data), Response::HTTP_UNAUTHORIZED);
    }

    public static function FORBIDDEN(string $errorMessage = null): Response
    {
        $data = [
            self::ERROR_CODE => self::ERROR_CODE_FORBIDDEN,
        ];

        if (!empty($errorMessage)) {
            $data[self::ERROR_MESSAGE] = $errorMessage;
        }

        return new Response(json_encode($data), Response::HTTP_FORBIDDEN);
    }
}