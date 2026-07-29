<?php

namespace App\Domains\Playlist\Repositories;

use App\Domains\Playlist\Models\Playlist;
use App\Domains\Playlist\Repositories\Contracts\PlaylistRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentPlaylistRepository implements PlaylistRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?Playlist
    {
        return Playlist::query()
            ->with(['items.mediaAsset', 'items.logo', 'items.announcement'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function findById(int $id): ?Playlist
    {
        return Playlist::query()->with(['items.mediaAsset', 'items.logo', 'items.announcement'])->find($id);
    }

    public function create(array $payload): Playlist
    {
        return Playlist::query()->create($payload);
    }

    public function update(Playlist $playlist, array $payload): Playlist
    {
        $playlist->update($payload);

        return $playlist->refresh();
    }

    public function delete(Playlist $playlist): void
    {
        $playlist->delete();
    }

    private function query(array $filters = []): Builder
    {
        return Playlist::query()
            ->with(['items.mediaAsset', 'items.logo', 'items.announcement'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('title');
    }
}
