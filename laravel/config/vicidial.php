<?php

return [
    'enabled' => (bool) env('VICIDIAL_ENABLED', false),
    'ca_bundle' => env('VICIDIAL_CA_BUNDLE', ''),
    'api_url' => env('VICIDIAL_API_URL', ''),
    'api_user' => env('VICIDIAL_API_USER', ''),
    'api_password' => env('VICIDIAL_API_PASSWORD', ''),
    'agent_url' => env('VICIDIAL_AGENT_URL', ''),
    'ws_url' => env('VICIDIAL_WSS_URL', ''),
    'sip_domain' => env('VICIDIAL_SIP_DOMAIN', ''),
    'ice_servers' => json_decode(env('VICIDIAL_ICE_SERVERS', '[]'), true) ?: [],
    // Keys are this application's user IDs, never agent IDs supplied by a browser.
    'agents' => json_decode(env('VICIDIAL_AGENTS', '{}'), true) ?: [],
    'dispositions' => array_values(array_filter(array_map('trim', explode(',', env('VICIDIAL_DISPOSITIONS', ''))))),
    'conference_targets' => json_decode(env('VICIDIAL_CONFERENCE_TARGETS', '{}'), true) ?: [],
];
