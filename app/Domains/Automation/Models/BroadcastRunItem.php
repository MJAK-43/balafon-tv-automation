<?php

namespace App\Domains\Automation\Models;

use App\Domains\Media\Models\MediaAsset;
use App\Domains\Playlist\Models\PlaylistItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastRunItem extends Model
{
    protected $fillable = [
        'uuid',
        'broadcast_run_id',
        'playlist_item_id',
        'media_asset_id',
        'sequence',
        'state',
        'vmix_input_key',
        'vmix_input_number',
        'last_known_position_ms',
        'last_known_duration_ms',
        'context',
        'error_message',
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

    public function run(): BelongsTo
    {
        return $this->belongsTo(BroadcastRun::class, 'broadcast_run_id');
    }

    public function playlistItem(): BelongsTo
    {
        return $this->belongsTo(PlaylistItem::class);
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }
}
