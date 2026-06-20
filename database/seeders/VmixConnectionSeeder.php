<?php

namespace Database\Seeders;

use App\Domains\Vmix\Models\VmixConnection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VmixConnectionSeeder extends Seeder
{
    public function run(): void
    {
        VmixConnection::query()->updateOrCreate(
            ['name' => 'vMix local'],
            [
                'uuid' => (string) Str::uuid(),
                'host' => config('balafon.vmix.default_host'),
                'port' => config('balafon.vmix.default_port'),
                'timeout_ms' => config('balafon.vmix.default_timeout_ms'),
                'health_status' => 'unknown',
                'is_active' => true,
            ],
        );
    }
}
