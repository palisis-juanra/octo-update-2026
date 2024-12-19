<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

Route::get('/', function () {
    return new JsonResponse("octo.tourcms.com is working", Response::HTTP_OK);
});
