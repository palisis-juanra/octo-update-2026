<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class JSONLogService
{
    public string $correlationId;
    public string $channelId;
    public string $endpoint;
    public string $marketplaceId;
    public string | null $xRequestId;

    public function __construct(string $channelId, string $marketplaceId, string $endpoint, string $correlationId = null, string $xRequestId = null)
    {
        if (empty($correlationId)) {
            $correlationId = $correlationId = Str::uuid();
        }

        $this->correlationId = $correlationId;
        $this->channelId = $channelId;
        $this->marketplaceId = $marketplaceId;
        $this->endpoint = $endpoint;
        $this->xRequestId = $xRequestId;
    }

    public function info(string $message, string $logChannel = null, array $extraParams = []): void
    {
        $logJSON = $this->getLogAsJSON($message, $extraParams);

        if (isset($logChannel)) {
            Log::channel($logChannel)->info($logJSON);
        } else {
            Log::info($logJSON);
        }

    }

    public function error(string $message, string $logChannel = null, array $extraParams = []): void
    {
        $logJSON = $this->getLogAsJSON($message, $extraParams);

        if (isset($logChannel)) {
            Log::channel($logChannel)->error($logJSON);
        } else {
            Log::error($logJSON);
        }

    }

    public function debug(string $message, string $logChannel = null, array $extraParams = []): void
    {
        if (env('APP_DEBUG', false) === false) {
            return;
        }

        $logJSON = $this->getLogAsJSON($message, $extraParams);

        if (isset($logChannel)) {
            Log::channel($logChannel)->debug($logJSON);
        } else {
            Log::debug($logJSON);
        }

    }

    private function getLogAsJSON(string $message, array $extraParams = []): string
    {
        $logArray = $this->getBaseLogArray();
        $logArray['message'] = $message;

        if (!empty($extraParams)) {
            $this->addExtraParamsToLogArray($logArray, $extraParams);
        }

        return json_encode($logArray);
        
    }

    private function getBaseLogArray(): array
    {
        $baseLogArray = [
            'correlation_id' => $this->correlationId,
            'endpoint' => $this->endpoint,
            'channel_id' => $this->channelId,
            'marketplace_id' => $this->marketplaceId
        ];

        if (isset($this->xRequestId)) {
            $baseLogArray['x_request_id'] = $this->xRequestId;
        }

        return $baseLogArray;
    }

    private function addExtraParamsToLogArray(array &$logArray, array $extraParams): void
    {
        foreach ($extraParams as $key => $value) {

            if (empty($value)) {
                continue;
            }

            if (array_key_exists($key, $logArray)) {
                $key = "extra_{$key}";
            }
            $logArray[$key] = $value;
        }

    }

}