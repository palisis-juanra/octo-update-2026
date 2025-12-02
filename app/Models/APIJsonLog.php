<?php

namespace App\Models;

use stdClass;

class APIJsonLog
{
    public string $service = 'OCTO';
    public mixed $xRequestId;
    public mixed $xCorrelationId;
    public string $userAgent;
    public string $ipAddress;
    public array $accountIds;
    public array $channelIds;
    public array $tourIds;
    public mixed $maid;
    public string $action;
    public string $url;
    public string $capabilities = "";
    public int $executionTime;
    // Request
    public mixed $requestHeaders;
    public mixed $requestBody;
    public array $apiSpecificData = [];
    // Response
    public mixed $responseHeaders;
    public ?array $responseBody;
    public bool $errorLog;
    public string $verb;
    public int $timestamp;
    public string $success;
    public string $time;
    public string $message;
    public ?stdClass $exception = null;
    public string $error;
    public string $queryString;

    public function addApiSpecificData(array $apiSpecificData): self
    {
        if (empty($this->apiSpecificData)) {
            $this->apiSpecificData = $apiSpecificData;
        } else {
            $this->apiSpecificData = array_merge($this->apiSpecificData, $apiSpecificData);
        }
    
        return $this;
    }
}