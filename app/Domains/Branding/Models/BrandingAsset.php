<?php

namespace App\Domains\Branding\Models;

use App\Domains\Media\Services\MediaBrowserService;
use App\Domains\Playlist\Models\PlaylistItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrandingAsset extends Model
{
    protected $appends = [
        'storage_reference',
    ];

    protected $fillable = [
        'uuid',
        'name',
        'asset_type',
        'file_path',
        'text_content',
        'logo_position',
        'logo_scale',
        'text_color',
        'text_background_color',
        'text_font',
        'ticker_speed',
        'loop_enabled',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'loop_enabled' => 'boolean',
        'logo_scale' => 'integer',
        'ticker_speed' => 'integer',
    ];

    protected function filePath(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null
                ? null
                : app(MediaBrowserService::class)->presentPath($value),
            set: fn (?string $value): ?string => $value,
        );
    }

    protected function storageReference(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->getRawOriginal('file_path'),
        );
    }

    public function rawFilePath(): ?string
    {
        return $this->getRawOriginal('file_path');
    }

    public function logoPlaylistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class, 'logo_id');
    }

    public function announcementPlaylistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class, 'announcement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
