<?php

namespace App\Domains\System\Models;

use Illuminate\Database\Eloquent\Model;

class SystemDiagnostic extends Model
{
    protected $fillable = [
        'uuid',
        'machine_name',
        'os_name',
        'os_version',
        'vmix_detected',
        'vmix_version',
        'vmix_api_reachable',
        'tested_endpoints',
        'local_ip',
        'total_disk_bytes',
        'free_disk_bytes',
        'total_memory_bytes',
        'available_memory_bytes',
        'cpu_cores',
        'cpu_load_percent',
        'postgres_ok',
        'redis_ok',
        'scheduler_ok',
        'queue_workers_ok',
        'context',
        'checked_at',
    ];

    protected $casts = [
        'vmix_detected' => 'boolean',
        'vmix_api_reachable' => 'boolean',
        'tested_endpoints' => 'array',
        'postgres_ok' => 'boolean',
        'redis_ok' => 'boolean',
        'scheduler_ok' => 'boolean',
        'queue_workers_ok' => 'boolean',
        'context' => 'array',
        'checked_at' => 'datetime',
    ];
}
