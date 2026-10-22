<?php

return [
    'test_enabled' => env('TEST_CRM_ENABLED', false),
    'test_token' => env('TEST_CRM_API_TOKEN', ''),
    'base_url' => env('CRM_API_URL', ''),
    'token' => env('CRM_API_TOKEN', ''),
];
