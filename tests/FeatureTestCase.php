<?php

namespace Tests;

use App\Services\JSONLogService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class FeatureTestCase extends BaseTestCase
{
    public const AUTH_HEADER_NAME = 'Authorization';

    public const OCTO_INVALID_PATTERN_CREDENTIALS = 'Bearer NOVALIDKEY';

    public const OCTO_VALID_PATTERN_CREDENTIALS = 'Bearer 1|142|abc';

    public function getLoggerMock(): JSONLogService
    {
        $jsonLogServiceMock = $this->getMockBuilder(JSONLogService::class)
            ->getMock();

        $jsonLogServiceMock->method('getLogId')->willReturn('TestLogId');

        return $jsonLogServiceMock;
    }

    protected function getJsonLogMock(): JSONLogService
    {
        $jsonLog = $this->getMockBuilder(JSONLogService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['info', 'error', 'getLogId'])
            ->getMock();

        return $jsonLog;
    }
}
