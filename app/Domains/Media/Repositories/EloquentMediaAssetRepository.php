<?php

namespace App\Domains\Media\Repositories;

use App\Domains\Media\Models\MediaAsset;
use App\Domains\Media\Repositories\Contracts\MediaAssetRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentMediaAssetRepository implements MediaAssetRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage);
    }

    public function list(array $filters = []): Collection
    {
        return $this->query($filters)->get();
    }

    public function findByUuid(string $uuid): ?MediaAsset
    {
        return MediaAsset::query()->where('uuid', $uuid)->first();
    }

    public function findById(int $id): ?MediaAsset
    {
        return MediaAsset::query()->find($id);
    }

    public function create(array $payload): MediaAsset
    {
        return MediaAsset::query()->create($payload);
    }

    public function update(MediaAsset $mediaAsset, array $payload): MediaAsset
    {
        $mediaAsset->update($payload);

        return $mediaAsset->refresh();
    }

    public function delete(MediaAsset $mediaAsset): void
    {
        $mediaAsset->delete();
    }

    private function query(array $filters = []): Builder
    {
        return MediaAsset::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('file_path', 'like', "%{$search}%");
                });
            })
            ->when($filters['media_type'] ?? null, fn (Builder $query, string $mediaType) => $query->where('media_type', $mediaType))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('title');
    }
}
