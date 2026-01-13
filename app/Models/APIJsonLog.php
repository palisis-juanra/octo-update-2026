<?php

namespace App\Models;

use JsonSerializable;
use stdClass;

class APIJsonLog implements JsonSerializable
{
    public string $service = 'OCTO';
    public mixed $xRequestId;
    public mixed $xCorrelationId;
    public string $userAgent;
    public string $ipAddress;
    public array $accountIds = [];
    public array $channelIds = [];
    public array $tourIds = [];
    public mixed $maid;
    public string $action;
    public string $url;
    public string $capabilities = "";
    public int $executionTime;
    // Request
    public mixed $requestHeaders;
    public mixed $requestBody;
    public array $apiSpecificData = [] ;
    // Response
    public mixed $responseHeaders = [];
    public ?array $responseBody = [];
    public bool $errorLog = false;
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

    public function jsonSerialize(): mixed
    {
        $msg = [
            'request_id' => $this->xRequestId,
            'correlation_id' => $this->xCorrelationId,
            'user_agent' => $this->userAgent,
            'action' => $this->action,
            'ipaddress' => $this->ipAddress,
            'response_headers' => $this->responseHeaders,
            'response' => $this->responseBody,
            'path' => $this->url,
            'request_headers' => $this->requestHeaders,
            'request_body' => $this->requestBody,
            'request_querystring' => $this->queryString,
            'api_specific_data' => $this->apiSpecificData,
            'maid' => $this->maid,
            'accounts' => $this->accountIds,
            'channels' => $this->channelIds,
            'tours' => $this->tourIds,
            'execution_time' => $this->executionTime,
            'error_log' => $this->errorLog,
            'verb' => $this->verb,
            'timestamp' => $this->timestamp,
            'success' => $this->success,
            'error' => $this->error,
            'message' => $this->message,
        ];

        if ($this->exception) {
            $msg['exception'] = $this->exception;
        }

        return $msg;
    
    }
}