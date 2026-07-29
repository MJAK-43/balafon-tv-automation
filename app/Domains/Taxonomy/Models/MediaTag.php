<?php

namespace App\Domains\Taxonomy\Models;

use Illuminate\Database\Eloquent\Model;

class MediaTag extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
    ];
}
