<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class JSONLog extends Facade
{
    /**
    * @method static void info(array $infoArray)
    * @method static void error(array $errorArray)
    * @method static void notice(array $noticeArray)
    * @method static void info(array $infoArray)
    * @method static void debug(array $debugArray)
    * @method static void log(array $logArray)    
    */

    protected static function getFacadeAccessor()
    {
         return 'JSONLogService';
    }
}