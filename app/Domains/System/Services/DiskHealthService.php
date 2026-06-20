<?php

namespace App\Domains\System\Services;

class DiskHealthService
{
    public function check(string $path): array
    {
        return [
            'total_bytes' => @disk_total_space($path) ?: null,
            'free_bytes' => @disk_free_space($path) ?: null,
        ];
    }
}
