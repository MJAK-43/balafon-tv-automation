<?php

namespace App\Domains\System\Services;

class EnvironmentValidatorService
{
    public function requirements(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'php_version_ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'os_family' => PHP_OS_FAMILY,
            'target_php' => '8.4',
            'docker_expected' => true,
            'electron_planned' => true,
        ];
    }
}
