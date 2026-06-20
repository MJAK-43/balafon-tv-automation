<?php

namespace App\Domains\System\Services;

class CpuHealthService
{
    public function check(): array
    {
        $load = \function_exists('sys_getloadavg') ? \sys_getloadavg() : null;

        return [
            'cores' => (int) env('SYSTEM_CPU_CORES', 1),
            'load_percent' => is_array($load) && isset($load[0]) ? round($load[0] * 100 / max((int) env('SYSTEM_CPU_CORES', 1), 1), 2) : null,
        ];
    }
}
