<?php

namespace App\Domains\Media\Models;

use App\Domains\Media\Services\MediaBrowserService;
use App\Domains\Playlist\Models\PlaylistItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaAsset extends Model
{
    protected $appends = [
        'storage_reference',
    ];

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'media_type',
        'file_path',
        'duration_seconds',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function filePath(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): string => app(MediaBrowserService::class)->presentPath($value),
            set: fn (string $value): string => $value,
        );
    }

    protected function storageReference(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) $this->getRawOriginal('file_path'),
        );
    }

    public function rawFilePath(): string
    {
        return (string) $this->getRawOriginal('file_path');
    }

    public function playlistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class);
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
