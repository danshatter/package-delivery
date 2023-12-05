<?php

return [

    /**
     * The version of the application
     */
    'version' => '1.0.0',

    /**
     * The uses of our application
     */
    'uses' => [
        'business',
        'personal'
    ],

    /**
     * The amount of digits our OTP generates
     */
    'otp_digits_number' => 4,

    /**
     * The number of seconds which an OTP is valid
     */
    'otp_expiration_seconds' => 1800,

    /**
     * Hash used by the application for generating email verification hash
     */
    'verification_secret' => env('VERIFICATION_HASH'),

    /**
     * Hash used for generating token hash stored in the database
     */
    'auth_token_hash' => env('AUTH_TOKEN_HASH'),

    /**
     * The SMS provider details used by the application
     */
    'sms' => [
        'url' => env('SMS_SERVICE_ENDPOINT'),
        'auth_key' => env('SMS_SERVICE_AUTH_KEY'),
        'username' => env('SMS_SERVICE_USERNAME')
    ],

    /**
     * The URL of logo
     */
    'logo' => env('LOGO_URL'),

    /**
     * The relationships a next of kin has to the driver
     */
    'next_of_kin_relationships' => [
        'father',
        'mother',
        'brother',
        'sister',
        'son',
        'daughter',
        'wife',
        'husband'
    ],

    /**
     * The default currency of the application
     */
    'currency' => 'NGN',

    /**
     * The default search radius to look for available drivers in kilometres
     */
    'search_radius' => 2,

    /**
     * The default types of services Handova renders relating to packages
     */
    'services' => [
        'sending',
        'receiving'
    ],

    /**
     * The different payment methods of the application
     */
    'payment_methods' => [
        'wallet',
        'card'
    ],

    /**
     * The percentage price bound to use for minimum and maximum possible delivery price
     */
    'delivery_price_percentage_bound' => 10,

    /**
     * The percentage delivery time bound to use for minimum and maximum possible delivery time
     */
    'delivery_time_bound' => 15,

    /**
     * The percentage arrival time bound to use for minimum and maximum possible arrival time
     */
    'arrival_time_bound' => 10,

    /**
     * The number of paginated items per page
     */
    'per_page' => 20,

    /**
     * The method the transaction fee is deducted. (Any of "percentage" or "amount")
     */
    'transaction_fee' => [
        'type' => 'percentage',
        'value' => 5
    ],

    /**
     * The method the order cancellation fee is deducted. (Any of "percentage" or "amount")
     */
    'order_cancellation_fee' => [
        'type' => 'percentage',
        'value' => 5
    ]

];