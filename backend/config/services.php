<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'malia_pay' => [
        'api_url' => env('MALIA_PAY_API_URL', 'https://malia-pay.com/api/v1/OnlinePaymentService/add_payer'),
        'merchant_id' => env('MALIA_PAY_MERCHANT_ID', 'MI_AOXBNNUD2J'),
        'aggregated_merchant_id' => env('MALIA_PAY_AGGREGATED_MERCHANT_ID', 'am-1j54gkvb820we'),
    ],

];

