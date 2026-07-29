<?php

namespace App\Domains\Channel\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'code',
        'timezone',
        'description',
        'status',
    ];
}
