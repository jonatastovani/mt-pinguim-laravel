<?php

use App\Http\Middleware\TenancyMiddleware;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::domain('{tenancy}.'.config('app.domain'))->middleware(TenancyMiddleware::class)->group(function () {
    Route::get('/', function ($tenancy) {
        dump($tenancy);
        dd(User::first()->toArray());
        return view('welcome');
    });
});