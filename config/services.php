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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'jaze' => [
        'base_url' => env('JAZE_BASE_URL'),
        'basic_user' => env('JAZE_BASIC_USER', 'pho'),
        'basic_password' => env(
            'JAZE_BASIC_PASSWORD',
            '158e8dd8fffa77e221bc59087009f36bbea636e5',
        ),
        'timeout' => env('JAZE_TIMEOUT', 20),
    ],

    'zostream_isp' => [
        'subscribe_url' => env('ZOSTREAM_ISP_SUBSCRIBE_URL', 'https://apis.zostream.in/api/v3.0/zostream-isp/subscribe'),
        'timeout' => env('ZOSTREAM_ISP_TIMEOUT', 20),
    ],

];
