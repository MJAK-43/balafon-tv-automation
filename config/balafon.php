<?php

return [
    'overlay_base_url' => env('BALAFON_OVERLAY_BASE_URL', env('APP_URL', 'http://127.0.0.1:8080')),
    'vmix' => [
        'driver' => env('VMIX_DRIVER', 'real'),
        'default_host' => env('VMIX_HOST', 'host.docker.internal'),
        'default_port' => (int) env('VMIX_PORT', 8088),
        'default_timeout_ms' => (int) env('VMIX_TIMEOUT_MS', 3000),
        'ticker_title_path' => env(
            'VMIX_TICKER_TITLE_PATH',
            'C:\\Program Files (x86)\\vMix\\titles\\GT Ticker\\Ticker 3- Clear.gtzip'
        ),
        'ticker_text_index' => (int) env('VMIX_TICKER_TEXT_INDEX', 0),
    ],
    'media' => [
        'browser_roots' => array_values(array_filter(array_map(
            static fn (string $path): string => trim($path),
            explode(',', (string) env('BALAFON_MEDIA_BROWSER_ROOTS', storage_path('app')))
        ))),
        'host_browser_roots' => array_values(array_filter(array_map(
            static fn (string $path): string => trim($path),
            explode(',', (string) env('BALAFON_MEDIA_HOST_BROWSER_ROOTS', ''))
        ))),
        'container_workspace_path' => env('BALAFON_CONTAINER_WORKSPACE_PATH', base_path()),
        'host_workspace_path' => env('BALAFON_HOST_WORKSPACE_PATH'),
    ],
    'automation' => [
        'poll_interval_ms' => (int) env('BALAFON_AUTOMATION_POLL_INTERVAL_MS', 1000),
        'prepare_threshold_ms' => (int) env('BALAFON_AUTOMATION_PREPARE_THRESHOLD_MS', 2000),
        'transition_lead_ms' => (int) env('BALAFON_AUTOMATION_TRANSITION_LEAD_MS', 250),
        'stalled_grace_ms' => (int) env('BALAFON_AUTOMATION_STALLED_GRACE_MS', 4000),
    ],
];
