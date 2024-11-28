<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use App\Exceptions\CustomExceptionHandler;
use App\Services\JSONLogService;
use Illuminate\Http\Request;
use App\Http\Middleware\OctoAuthentication;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->bind(ExceptionHandler::class, CustomExceptionHandler::class);

        if (getenv('APP_ENV') !== 'testing') {
            // We need this to use JSONLog as facade
            $this->app->bind('JSONLogService', function(): JSONLogService {
                                    
                return app(JSONLogService::class);

            });
        }
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
