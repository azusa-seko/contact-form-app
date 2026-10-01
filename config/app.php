<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'name' => env('APP_NAME', 'Laravel'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    'timezone' => 'UTC',

    'locale' => 'en',

    'fallback_locale' => 'en',

    'faker_locale' => 'en_US',

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',   
    ],

    

    'providers' => ServiceProvider::defaultProviders()->merge([
        
        AppServiceProvider::class,
        AuthServiceProvider::class,
        
        EventServiceProvider::class,
        RouteServiceProvider::class,
        FortifyServiceProvider::class,
    ])->toArray(),


    'aliases' => Facade::defaultAliases()->merge([
        
    ])->toArray(),

];
