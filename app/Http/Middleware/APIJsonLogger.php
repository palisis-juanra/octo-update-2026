<?php

namespace App\Http\Middleware;

use App\Http\Requests\OctoRequest;
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
        } catch (Throwable $e) {
            $this->fillAfterException($apiJsonLog, $e);
        }

        // After the request is processed
        $this->fillAfterRequestIsProcessed($apiJsonLog, $response);

        $this->writeLog($apiJsonLog);

        return $response;
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
        $log->requestHeaders   = $request->headers->all();
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
        $apiJsonLog->responseHeaders = $response->headers->all();
        $apiJsonLog->success         = $response->isSuccessful() ? '1' : '0';
        $apiJsonLog->errorLog        = !$response->isSuccessful();
        $apiJsonLog->error           = $response->isSuccessful() ? '' : 'HTTP ' . $response->getStatusCode();
    }

    protected function fillAfterException(APIJsonLog $apiJsonLog, Throwable $e): void
    {
        $executionTimeMs = (int) round(microtime(true) * 1000) - $apiJsonLog->timestamp;

        $apiJsonLog->executionTime = $executionTimeMs;
        $apiJsonLog->success       = 0;
        $apiJsonLog->errorLog      = true;
        $apiJsonLog->error         = $e->getMessage();
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

        try {
            $channelId = (int) $request->input(OctoAuthentication::FIELD_CHANNEL_ID);
            $showChannel = $this->tourCMSService->showChannel($channelId);
            $accountId = !empty($showChannel->channel->account_id) ? (int) $showChannel->channel->account_id : 0;
        } catch (Throwable) {
            $channelId = $accountId = null;
        }

        $log->accountIds = [$accountId];
        $log->channelIds = [$channelId];

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
                    'booking_uuid' => $request->input(OctoRequest::UUID)
                ];
                break;
            case OctoRequest::ENDPOINT_BOOKINGS_CANCELLATION:
                $specificData = [
                    'booking_uuid' => $request->input(OctoRequest::UUID)
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
}