<?php

use App\Http\Controllers\SupplierController;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Responses\OctoResponse;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

Route::get('/', function () {
    return view('welcome');
});
