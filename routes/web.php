<?php

use App\Models\Tenancy;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::domain('{tenancy}.jetete.test')->group(function () {
    Route::get('/', function ($tenancy) {
        $tenancy = Tenancy::whereDomain($tenancy.'.jetete.test')->firstOrFail();
        dd($tenancy->toArray());
        Config::set('database.connections.tenancy', $tenancy->database);
    });
    return view('welcome');
});