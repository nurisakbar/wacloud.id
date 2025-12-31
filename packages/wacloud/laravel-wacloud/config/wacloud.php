<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WACloud API Key
    |--------------------------------------------------------------------------
    |
    | API Key Anda dari dashboard WACloud.
    | Dapatkan di: https://app.wacloud.id
    |
    */
    'api_key' => env('WACLOUD_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | WACloud Base URL
    |--------------------------------------------------------------------------
    |
    | Base URL untuk WACloud API.
    | Default: https://app.wacloud.id/api/v1
    |
    */
    'base_url' => env('WACLOUD_BASE_URL', 'https://app.wacloud.id/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout untuk HTTP request dalam detik.
    |
    */
    'timeout' => env('WACLOUD_TIMEOUT', 30),
];

