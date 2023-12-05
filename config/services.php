<?php

return [

    /**
     * The firebase JWT package
     */
    'firebase_jwt' => [
        'secret' => env('FIREBASE_JWT_SECRET'),
        'expires_in' => 3000,
    ],
    
    /**
     * The firebase cloud message configuration
     */
    'firebase_messaging' => [
        'endpoint' => env('FIREBASE_MESSAGING_ENDPOINT'),
        'web_api_key' => env('FIREBASE_MESSAGING_WEB_API_KEY'),
        'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'auth_scope' => env('FIREBASE_MESSAGING_AUTH_SCOPE'),
        'project_id' => env('FIREBASE_MESSAGING_PROJECT_ID')
    ],

    /**
     * The Google OAuth2 configuration
     */
    'google_oauth2' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET')
    ],

    /**
     * The Google APIs configuration
     */
    'google_apis' => [
        'key' => env('GOOGLE_API_KEY'),

        'geocoding' => [
            'endpoint' => env('GOOGLE_API_GEOCODING_ENDPOINT'),
        ],

        'geolocation' => [
            'endpoint' => env('GOOGLE_API_GEOLOCATION_ENDPOINT'),
        ],

        'direction' => [
            'endpoint' => env('GOOGLE_API_DIRECTION_ENDPOINT'),
        ],
    ],

    /**
     * The Paystack credentials
     */
    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY')
    ],

    /**
     * The Verify Me credentials
     */
    'verify_me' => [
        'public_key' => env('VERIFY_ME_PUBLIC_KEY'),
        'secret_key' => env('VERIFY_ME_SECRET_KEY')
    ]

];