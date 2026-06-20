<?php

namespace App\Domains\Audit\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'actor_id',
        'actor_type',
        'action',
        'entity_type',
        'entity_id',
        'context',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];
}
