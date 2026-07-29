<?php

namespace App\Domains\Playlist\Models;

use App\Domains\Branding\Models\BrandingAsset;
use App\Domains\Media\Models\MediaAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaylistItem extends Model
{
    protected $fillable = [
        'uuid',
        'playlist_id',
        'position',
        'media_asset_id',
        'logo_id',
        'announcement_id',
    ];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(BrandingAsset::class, 'logo_id');
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(BrandingAsset::class, 'announcement_id');
    }
}
