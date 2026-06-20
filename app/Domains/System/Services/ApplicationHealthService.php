<?php

namespace App\Domains\System\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApplicationHealthService
{
    public function check(): array
    {
        return [
            'postgres_ok' => $this->checkDatabase(),
            'redis_ok' => $this->checkRedis(),
            'scheduler_ok' => true,
            'queue_workers_ok' => true,
        ];
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Cache::store(config('cache.default'))->get('balafon-health-check');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
