<?php

namespace App\Domains\Vmix\Models;

use Illuminate\Database\Eloquent\Model;

class VmixConnection extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'host',
        'port',
        'api_password',
        'timeout_ms',
        'health_status',
        'last_health_check_at',
        'is_active',
    ];

    protected $casts = [
        'last_health_check_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
