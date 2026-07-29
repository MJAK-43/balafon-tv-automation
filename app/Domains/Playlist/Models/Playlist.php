<?php

namespace App\Domains\Playlist\Models;

use App\Domains\Scheduling\Models\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Playlist extends Model
{
    protected $fillable = [
        'uuid',
        'title',
        'description',
        'status',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PlaylistItem::class)->orderBy('position');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    protected $appends = [
        'items_count',
        'total_duration_seconds',
    ];

    public function getItemsCountAttribute(): int
    {
        return $this->relationLoaded('items') ? $this->items->count() : $this->items()->count();
    }

    public function getTotalDurationSecondsAttribute(): int
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->with('mediaAsset')->get();

        return (int) $items->sum(fn (PlaylistItem $item) => $item->mediaAsset?->duration_seconds ?? 0);
    }
}
