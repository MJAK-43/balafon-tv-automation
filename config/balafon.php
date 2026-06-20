<?php

return [
    'vmix' => [
        'driver' => env('VMIX_DRIVER', 'real'),
        'default_host' => env('VMIX_HOST', 'host.docker.internal'),
        'default_port' => (int) env('VMIX_PORT', 8088),
        'default_timeout_ms' => (int) env('VMIX_TIMEOUT_MS', 3000),
    ],
];
