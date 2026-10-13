<?php

return [
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

    'backend' => [
        'url' => env('BACKEND_URL'),
        'ws_url' => env('BACKEND_WS_URL', ''),
        'internal_token' => env('BACKEND_INTERNAL_TOKEN'),
    ],

    'webrtc' => [
        'ws' => env('WEBRTC_WS', 'ws://192.168.18.31:5066'),
        'domain' => env('WEBRTC_SIP_DOMAIN') ?: (function () {
            $appUrl = env('APP_URL', 'http://localhost');
            $parsed = parse_url($appUrl);
            return $parsed['host'] ?? 'localhost';
        })(),
        'username' => env('WEBRTC_SIP_USER', '1000'),
        'password' => env('WEBRTC_SIP_PASSWORD', '1234'),
        'ice_servers' => array_values(array_filter(array_map('trim', explode(',', env('WEBRTC_ICE_SERVERS', 'stun:stun.l.google.com:19302'))))),
    ],
];
