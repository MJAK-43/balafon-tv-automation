<?php

namespace App\Domains\Automation\Models;

use App\Domains\Media\Models\MediaAsset;
use App\Domains\Playlist\Models\PlaylistItem;
use App\Domains\Scheduling\Models\Schedule;
use App\Domains\Vmix\Models\VmixConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroadcastRun extends Model
{
    protected $fillable = [
        'uuid',
        'schedule_id',
        'vmix_connection_id',
        'state',
        'current_playlist_item_id',
        'current_media_asset_id',
        'current_sequence',
        'context',
        'last_error',
        'started_at',
        'completed_at',
        'failed_at',
        'skipped_at',
    ];

    protected $casts = [
        'context' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'skipped_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(VmixConnection::class, 'vmix_connection_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BroadcastRunItem::class)->orderBy('sequence');
    }

    public function currentPlaylistItem(): BelongsTo
    {
        return $this->belongsTo(PlaylistItem::class, 'current_playlist_item_id');
    }

    public function currentMediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'current_media_asset_id');
    }
}
