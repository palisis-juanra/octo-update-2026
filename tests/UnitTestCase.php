<?php

namespace Tests;

use App\Services\JSONLogService;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;
use ReflectionMethod;

abstract class UnitTestCase extends BaseTestCase
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

    public static function getProtectedMethod(object $obj, $name): ReflectionMethod
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }
}
