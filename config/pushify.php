<?php

return [
    'provider' => env('PUSHIFY_PROVIDER', 'firebase'),

    'routes' => [
        'enabled' => env('PUSHIFY_ROUTES_ENABLED', true),
        'prefix' => env('PUSHIFY_ROUTE_PREFIX', 'pushify'),
        'middleware' => ['api'],
    ],

    'providers' => [
        'firebase' => \Badawy\Pushify\Providers\FirebaseProvider::class,
        'onesignal' => \Badawy\Pushify\Providers\OneSignalProvider::class,
    ],

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'topic' => 'all',
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'token_cache_key' => 'pushify_firebase_access_token',
    ],

    'onesignal' => [
        'app_id' => env('ONESIGNAL_APP_ID'),
        'api_key' => env('ONESIGNAL_API_KEY'),
        'api_url' => env('ONESIGNAL_API_URL', 'https://api.onesignal.com/notifications'),
    ],

    'log_payload' => env('PUSHIFY_LOG_PAYLOAD', false),
];
