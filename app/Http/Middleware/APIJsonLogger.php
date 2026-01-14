<?php

namespace App\Http\Middleware;

use App\Http\Requests\OctoRequest;
use App\Http\Responses\OctoResponse;
use App\Models\APIJsonLog;
use App\Services\TourCMSService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Closure;
use Throwable;

/**
 * Middleware used to fill APIJsonModel
 */
class APIJsonLogger
{
    protected TourCMSService $tourCMSService;

    /** @var string[] */
    private array $sensitiveHeaders = [
        'authorization',
        'php-auth-pw',
        'x-api-key',
    ];

    /**
     * This method fills APIJsonLog data before and after the request is processed
     * @return void
     */
    public function handle(Request $request, Closure $next): JsonResponse
    {
        if (env('APP_ENV') == 'testing') {
            return $next($request);
        }

        $this->tourCMSService = app(TourCMSService::class);

        $apiJsonLog = new APIJsonLog();

        // Before the request is processed
        $this->fillBeforeRequestIsProcessed($request, $apiJsonLog);

        // We save the log in the request in order to be accesible in controllers to fill specific data
        $request->attributes->set('apiJsonLog', $apiJsonLog);

        // We let others middlewares/controller handle the request
        try {
            $response = $next($request);
            $this->fillAfterRequestIsProcessed($apiJsonLog, $response);
            $this->writeLog($apiJsonLog);
            return $response;
        } catch (Throwable $e) {
            $this->fillAfterException($apiJsonLog, $e);
            $this->writeLog($apiJsonLog);
            return $next($request);
        }
    }

    /* PROTECTED METHODS */
    protected function fillBeforeRequestIsProcessed(Request $request, APIJsonLog $log): APIJsonLog
    {
        $log->timestamp = round(microtime(true) * 1000);
        $log->message          = '';
        $log->xRequestId       = $request->headers->get(OctoAuthentication::FIELD_X_REQUEST_ID);
        $log->xCorrelationId   = $request->headers->get(OctoAuthentication::FIELD_X_CORRELATION_ID);
        $log->userAgent        = $request->userAgent() ?? '';
        $log->ipAddress        = $request->ip() ?? '';
        $log->url              = $request->path();
        $log->verb             = $request->method();
        $log->requestHeaders   = $this->maskSensitiveHeaders($request->headers->all());
        $log->requestBody      = $this->getRequestBody($request);
        $log->queryString      = $request->getQueryString() ?? '';
        $log->action           = $request->route()->getName();
        $log->maid             = $request->input(OctoAuthentication::FIELD_MAID);

        $this->setSpecificData($log, $request);
        $this->setTourCMSData($log, $request);

        return $log;
    }

    protected function fillAfterRequestIsProcessed(APIJsonLog $apiJsonLog, JsonResponse $response): void
    {
        $executionTimeMs = (int) round(microtime(true) * 1000) - $apiJsonLog->timestamp;

        $apiJsonLog->executionTime   = $executionTimeMs;
        $apiJsonLog->responseBody    = $this->getResponseBody($response);
        $apiJsonLog->responseHeaders = $this->maskSensitiveHeaders($response->headers->all());
        $apiJsonLog->success         = $response->isSuccessful() ? '1' : '0';
        $responseData = json_decode($response->getContent(), true);
        $apiJsonLog->error           = $response->isSuccessful() ? 'OK' : $responseData['error'] ?? OctoResponse::ERROR_CODE_INTERNAL_SERVER_ERROR;
    }

    protected function fillAfterException(APIJsonLog $apiJsonLog, Throwable $e): void
    {
        $executionTimeMs = (int) round(microtime(true) * 1000) - $apiJsonLog->timestamp;

        $apiJsonLog->executionTime = $executionTimeMs;
        $apiJsonLog->success       = 0;
        $apiJsonLog->error         = OctoResponse::ERROR_CODE_INTERNAL_SERVER_ERROR;
        $apiJsonLog->exception     = (object) [
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ];
        $apiJsonLog->timestamp     = time();
    }

    protected function writeLog(APIJsonLog $APIJsonLog): void
    {
        $encodedJSONLog = json_encode($APIJsonLog, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $APIJsonLogFile = env('API_JSON_LOGS_FILE');
        error_log($encodedJSONLog, 3, $APIJsonLogFile);
        error_log(PHP_EOL, 3, $APIJsonLogFile);
    }

    private function getRequestBody(Request $request): mixed
    {
        if (in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH'])) {
            $content = $request->getContent();
            if ($content === '' || $content === null) {
                return null;
            }

            $json = json_decode($content, true);
            return $json !== null ? $json : $content;
        }

        return null;
    }

    private function getResponseBody(JsonResponse $response): mixed
    {
        $content = $response->getContent();

        if ($content === '' || $content === null) {
            return null;
        }

        $json = json_decode($content, true);
        return $json !== null ? $json : $content;
    }

    /**
     * Set TourCMS data: account ID and channel ID
     * @param APIJsonLog $log
     * @param Request $request
     * @return APIJsonLog
     */
    private function setTourCMSData(APIJsonLog $log, Request $request): APIJsonLog
    {

        $tourId = null;

        try {
            $channelId = (int) $request->input(OctoAuthentication::FIELD_CHANNEL_ID);
            $showChannel = $this->tourCMSService->showChannel($channelId);
            $accountId = !empty($showChannel->channel->account_id) ? (int) $showChannel->channel->account_id : 0;
            
            $productId = $request->input('productId');
            if (!empty($productId)) {
                $productIdExploded = explode('|', $productId)[0];
                $distributionIdentifierSplitted = explode('_', $productIdExploded);
                $tourId = !empty($distributionIdentifierSplitted[2]) ? (int)$distributionIdentifierSplitted[2] : "";
            }
        } catch (Throwable) {
            $channelId = $accountId = $tourId = null;
        }

        $log->accountIds = [$accountId];
        $log->channelIds = [$channelId];
        if (!empty($tourId)) {
            $log->tourIds = [$tourId];
        }

        return $log;
    }

    private function setSpecificData(APIJsonLog $log, Request $request): APIJsonLog
    {
        $routeName = $request->route()->getName();

        switch ($routeName) {
            case OctoRequest::ENDPOINT_AVAILABILITY_CHECK:
                $specificData = [
                    'product_id' => $request->input(OctoRequest::PRODUCT_ID),
                    'option_id' => $request->input(OctoRequest::OPTION_ID),
                    'availability_id' => $request->input(OctoRequest::AVAILABILITY_ID),
                    'unit_items' => $request->input(OctoRequest::UNITS)
                ];
                break;
            case OctoRequest::ENDPOINT_BOOKINGS_RESERVATION:
                $specificData = [
                    'product_id' => $request->input(OctoRequest::PRODUCT_ID),
                    'option_id' => $request->input(OctoRequest::OPTION_ID),
                    'availability_id' => $request->input(OctoRequest::AVAILABILITY_ID),
                    'unit_items' => $request->input(OctoRequest::UNIT_ITEMS),
                ];
                break;
            case OctoRequest::ENDPOINT_BOOKINGS_CONFIRMATION:
                $specificData = [
                    'booking_uuid' => $request->route(OctoRequest::UUID)
                ];
                break;
            case OctoRequest::ENDPOINT_BOOKINGS_CANCELLATION:
                $specificData = [
                    'booking_uuid' => $request->route(OctoRequest::UUID)
                ];
                break;
            default:
                break;
        }

        $log->addApiSpecificData([
            ... $specificData,
            'capabilities' => $request->headers->get('Octo-Capabilities', "")
        ]);

        return $log;
    }

    private function maskSensitiveHeaders(array $headers): array
    {
        $normalizedSensitive = array_map('strtolower', $this->sensitiveHeaders);

        foreach ($headers as $name => &$values) {
            if (in_array(strtolower($name), $normalizedSensitive, true)) {
                foreach ($values as &$value) {
                    $value = $this->maskHeaderValue($name, $value);
                }
            }
        }

        return $headers;
    }

    private function maskHeaderValue(string $name, string $value): string
    {
        $lower = strtolower($name);

        // Authorization: Basic xxx / Bearer yyy
        if ($lower === 'authorization') {
            if (preg_match('/^(Basic|Bearer)\s+(.+)$/i', $value, $m)) {
                $scheme      = $m[1]; // Basic / Bearer
                $credentials = $m[2];

                return $scheme . ' ' . $this->maskStringKeepLast4($credentials);
            }

            return $this->maskStringKeepLast4($value);
        }

        if ($lower === 'php-auth-pw') {
            return $this->maskStringKeepLast4($value);
        }

        return $this->maskStringKeepLast4($value);
    }

    private function maskStringKeepLast4(string $value): string
    {
        $len = strlen($value);

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        $visible = substr($value, -4);
        $masked  = str_repeat('*', $len - 4);

        return $masked . $visible;
    }
}