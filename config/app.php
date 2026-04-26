<?php

return [
    'name'     => env('APP_NAME', 'souzou-soft'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),
    'url'      => env('APP_URL', 'http://localhost'),
    'timezone' => 'Asia/Tokyo',
    'locale'   => 'ja',
    'fallback_locale' => 'en',
    'faker_locale'    => 'ja_JP',
    'cipher'   => 'AES-256-CBC',
    'key'      => env('APP_KEY'),
    'previous_keys' => [],
    'maintenance' => [
        'driver' => 'file',
    ],
];
