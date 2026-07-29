<?php

namespace App\Domains\Playlist\Services;

use App\Domains\Playlist\Models\Playlist;
use App\Domains\Playlist\Repositories\Contracts\PlaylistRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlaylistService
{
    public function __construct(
        private readonly PlaylistRepositoryInterface $playlists,
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->playlists->paginate($filters, $perPage);
    }

    public function findByUuid(string $uuid): ?Playlist
    {
        return $this->playlists->findByUuid($uuid);
    }

    public function create(array $payload): Playlist
    {
        return DB::transaction(function () use ($payload): Playlist {
            $items = $payload['items'] ?? [];
            unset($payload['items']);

            $payload['uuid'] = (string) Str::uuid();
            $playlist = $this->playlists->create($payload);

            $this->syncItems($playlist, $items);

            return $playlist->load(['items.mediaAsset', 'items.logo', 'items.announcement']);
        });
    }

    public function update(Playlist $playlist, array $payload): Playlist
    {
        return DB::transaction(function () use ($playlist, $payload): Playlist {
            $items = $payload['items'] ?? [];
            unset($payload['items']);

            $playlist = $this->playlists->update($playlist, $payload);
            $this->syncItems($playlist, $items);

            return $playlist->load(['items.mediaAsset', 'items.logo', 'items.announcement']);
        });
    }

    public function delete(Playlist $playlist): void
    {
        $this->playlists->delete($playlist);
    }

    public function syncItems(Playlist $playlist, array $items): void
    {
        $playlist->items()->delete();

        foreach ($items as $item) {
            $playlist->items()->create([
                'uuid' => (string) Str::uuid(),
                'position' => $item['position'],
                'media_asset_id' => $item['media_asset_id'],
                'logo_id' => $item['logo_id'] ?? null,
                'announcement_id' => $item['announcement_id'] ?? null,
            ]);
        }
    }
}
