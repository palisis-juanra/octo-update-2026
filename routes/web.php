<?php

use App\Http\Controllers\SupplierController;
use App\Http\Middleware\OctoAuthentication;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('supplier', [SupplierController::class, 'index'])->middleware([OctoAuthentication::class]);
