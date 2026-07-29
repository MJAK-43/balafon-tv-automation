<?php

namespace App\Domains\Scheduling\Models;

use App\Domains\Channel\Models\Channel;
use App\Domains\Playlist\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Schedule extends Model
{
    protected $fillable = [
        'uuid',
        'channel_id',
        'playlist_id',
        'starts_at',
        'ends_at',
        'status',
        'created_by',
        'updated_by',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function startsAt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : Carbon::parse($value, 'UTC'),
            set: fn ($value) => $value === null ? null : Carbon::parse($value, 'UTC')->format('Y-m-d H:i:s'),
        );
    }

    protected function endsAt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : Carbon::parse($value, 'UTC'),
            set: fn ($value) => $value === null ? null : Carbon::parse($value, 'UTC')->format('Y-m-d H:i:s'),
        );
    }
}
