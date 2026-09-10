<?php
// Standalone configuration regression: php tests/socket-url-regression.php
// No application bootstrap, credentials, network, or database required.
$environment = [];
function env($key, $default = null) {
    global $environment;
    return array_key_exists($key, $environment) ? $environment[$key] : $default;
}
foreach ([
    [['BACKEND_URL' => 'http://127.0.0.1:4000'], ''],
    [['BACKEND_URL' => 'http://backend:4000'], ''],
    [['BACKEND_URL' => 'http://127.0.0.1:4000', 'BACKEND_WS_URL' => ''], ''],
    [['BACKEND_URL' => 'http://127.0.0.1:4000', 'BACKEND_WS_URL' => 'https://events.example.com'], 'https://events.example.com'],
] as [$environment, $expected]) {
    $config = require __DIR__.'/../config/services.php';
    if ($config['backend']['ws_url'] !== $expected) {
        throw new RuntimeException('Unexpected browser Socket.IO URL');
    }
    if ($config['backend']['url'] !== $environment['BACKEND_URL']) {
        throw new RuntimeException('Server-to-server backend URL changed');
    }
}
echo "PASS: same-origin defaults and explicit public URL override; internal API URL preserved\n";
