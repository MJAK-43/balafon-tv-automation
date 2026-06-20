<?php

namespace App\Domains\Vmix\Models;

use Illuminate\Database\Eloquent\Model;

class VmixCommandLog extends Model
{
    protected $fillable = [
        'uuid',
        'vmix_connection_id',
        'command_name',
        'request_url',
        'request_payload',
        'response_code',
        'response_body',
        'status',
        'duration_ms',
        'error_message',
        'executed_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'duration_ms' => 'integer',
        'executed_at' => 'datetime',
    ];
}
