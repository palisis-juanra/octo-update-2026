<?php

namespace Tests;

use App\Services\JSONLogService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    const AUTH_HEADER_NAME = 'Authorization';
    const OCTO_INVALID_PATTERN_CREDENTIALS = 'Bearer NOVALIDKEY';
    const OCTO_VALID_PATTERN_CREDENTIALS = 'Bearer 1|142|abc';

    public function getLoggerMock(): JSONLogService
    {
        $jsonLogServiceMock = $this->getMockBuilder(JSONLogService::class)
            ->getMock();

        $jsonLogServiceMock->method('getLogId')->willReturn('TestLogId');

        return $jsonLogServiceMock;
    }
}
