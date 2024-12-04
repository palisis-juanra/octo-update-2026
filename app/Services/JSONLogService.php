<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Throwable;

class JSONLogService
{
    public string $correlationId;
    public string $channelId;
    public string $endpoint;
    public string $marketplaceId;
    public string $xRequestId;
    public const LOG_TYPE_INFO = 'info';
    public const LOG_TYPE_ERROR = 'error';
    public const LOG_TYPE_WARNING = 'warning';
    public const LOG_TYPE_DEBUG = 'debug';
    public const LOG_TYPE_LOG = 'log';
    public const LOG_TYPES = [
        self::LOG_TYPE_INFO,
        self::LOG_TYPE_ERROR,
        self::LOG_TYPE_WARNING,
        self::LOG_TYPE_DEBUG,
        self::LOG_TYPE_LOG  
    ];

    public function __construct(string $channelId = '', string $marketplaceId = '', string $endpoint = '', string $correlationId = null, string $xRequestId = '')
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

    public function getLogId(): string
    {
        return $this->correlationId;
    }

    public function info(array|string $log): void
    {
        $logArray = is_string($log) ? ["message" => $log] : $log;
    
        $this->write(self::LOG_TYPE_INFO, $logArray);
    }

    public function error(array|string $log): void
    {
        $logArray = is_string($log) ? ["message" => $log] : $log;

        if ($this->isAnExceptionLog($logArray)) {
            $exception = $logArray['exception'];
            $exceptionInfo = $this->getExceptionInfoAsArray($exception);
            unset($logArray['exception']);
            $logArray = $this->addEntriesToLogArray($logArray, $exceptionInfo);
        }

        $this->write(self::LOG_TYPE_ERROR, $logArray);
    }

    public function warning(array|string $log): void
    {
        $logArray = is_string($log) ? ["message" => $log] : $log;

        $this->write(self::LOG_TYPE_WARNING, $logArray);
    }

    public function debug(array|string $log): void
    {
        if (env('APP_DEBUG', false) === false) {
            return;
        }

        $logArray = is_string($log) ? ["message" => $log] : $log;

        $this->write(self::LOG_TYPE_DEBUG, $logArray);
    }

    public function log(array|string $log): void
    {
        $logArray = is_string($log) ? ["message" => $log] : $log;

        $this->write(self::LOG_TYPE_LOG, $logArray);
    }

    protected function write(string $logType, array $logArray)
    {

        if (empty($logArray) || !in_array($logType, self::LOG_TYPES)) {
            return;
        }

        $baseLogArray = $this->getBaseLogArray();
        $logArray = $this->addEntriesToLogArray($baseLogArray, $logArray);

        $log = json_encode($logArray);
        $this->removeWhiteSpacesFromLog($log);

        Log::$logType($log);

        return;
    }

    protected function getBaseLogArray(): array
    {

        if (!empty($this->xRequestId)) {
            $baseLogArray['xRequestId'] = $this->xRequestId;
        }
        $baseLogArray['correlationId'] = $this->correlationId;
        $baseLogArray['endpoint'] = $this->endpoint;
        $baseLogArray['channelId'] = $this->channelId;
        $baseLogArray['marketplaceId'] = $this->marketplaceId;
    
        return $baseLogArray;
    }

    // Exception logging
    protected function isAnExceptionLog(array $logArray): bool
    {
        return isset($logArray['exception']) && $logArray['exception'] instanceof Throwable;
    }

    protected function getExceptionInfoAsArray(Throwable $e): array
    {
        return [
            "exceptionMessage" => $e->getMessage(),
            "exceptionCode" => $e->getCode(),
            "exceptionTrace" => $e->getTrace()
        ];
    }
    protected function addEntriesToLogArray(array $logArray, array $entriesToAdd): array
    {
        return array_merge($logArray, $entriesToAdd);
    }

    protected function removeWhiteSpacesFromLog(string $log): string
    {
        $log = str_replace('\n', '', $log);
        $log = str_replace('\r', '', $log);
        $log = str_replace(PHP_EOL, '', $log);

        return $log;
    }

}