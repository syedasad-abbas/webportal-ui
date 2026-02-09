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
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'backend' => [
        'url' => env('BACKEND_URL', 'http://backend:4000'),
        'ws_url' => env('BACKEND_WS_URL', env('BACKEND_URL', 'http://localhost:4000')),
        'internal_token' => env('BACKEND_INTERNAL_TOKEN'),
    ],

    'webrtc' => [
        'ws' => env('WEBRTC_WS', 'wss://localhost:7443'),
        'domain' => env('WEBRTC_SIP_DOMAIN', 'webphone.local'),
        'username' => env('WEBRTC_SIP_USER', '1000'),
        'password' => env('WEBRTC_SIP_PASSWORD', '1234'),
        'ice_servers' => array_values(array_filter(array_map('trim', explode(',', env('WEBRTC_ICE_SERVERS', 'stun:stun.l.google.com:19302'))))),
    ],


];
