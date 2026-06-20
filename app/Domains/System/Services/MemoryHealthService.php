<?php

namespace App\Domains\System\Services;

class MemoryHealthService
{
    public function check(): array
    {
        $memoryLimit = ini_get('memory_limit');
        $limitBytes = $this->toBytes($memoryLimit);

        return [
            'total_bytes' => $limitBytes,
            'available_bytes' => $limitBytes !== null ? max($limitBytes - memory_get_usage(true), 0) : null,
        ];
    }

    private function toBytes(string|false $value): ?int
    {
        if ($value === false || $value === '-1') {
            return null;
        }

        $unit = strtolower(substr($value, -1));
        $bytes = (int) $value;

        return match ($unit) {
            'g' => $bytes * 1024 * 1024 * 1024,
            'm' => $bytes * 1024 * 1024,
            'k' => $bytes * 1024,
            default => (int) $value,
        };
    }
}
