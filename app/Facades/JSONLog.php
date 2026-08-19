<?php

namespace App\Facades;

use App\Services\JSONLogService;
use Illuminate\Support\Facades\Facade;

class JSONLog extends Facade
{
    /**
     * @method static void info(array|string $infoArray)
     * @method static void error(array|string $errorArray)
     * @method static void notice(array|string $noticeArray)
     * @method static void info(array|string $infoArray)
     * @method static void debug(array|string $debugArray)
     * @method static void log(array|string $logArray)
     */
    protected static function getFacadeAccessor(): string
    {
        return JSONLogService::class;
    }
}
