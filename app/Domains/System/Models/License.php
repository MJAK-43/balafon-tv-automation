<?php

namespace App\Domains\System\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'uuid',
        'license_key',
        'license_type',
        'customer_name',
        'customer_email',
        'machine_fingerprint',
        'activated_at',
        'expires_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];
}
