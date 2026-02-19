<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fixtures Path
    |--------------------------------------------------------------------------
    |
    | The path where custom fixture files are located. If null, the default
    | fixtures bundled with the package will be used.
    |
    */
    'fixtures_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Prevent Stray Requests
    |--------------------------------------------------------------------------
    |
    | When enabled, any YouTube API request that doesn't have a matching fake
    | response queued will throw a StrayRequestException. This is useful in
    | testing to ensure all API calls are accounted for.
    |
    */
    'prevent_stray_requests' => false,
];
