<?php

namespace App\Domains\Taxonomy\Repositories;

use App\Domains\Taxonomy\Models\MediaTag;
use App\Domains\Taxonomy\Repositories\Contracts\MediaTagRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentMediaTagRepository implements MediaTagRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return MediaTag::query()->orderBy('name')->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?MediaTag
    {
        return MediaTag::query()->where('uuid', $uuid)->first();
    }

    public function create(array $payload): MediaTag
    {
        return MediaTag::query()->create($payload);
    }

    public function update(MediaTag $tag, array $payload): MediaTag
    {
        $tag->update($payload);

        return $tag->refresh();
    }

    public function delete(MediaTag $tag): void
    {
        $tag->delete();
    }
}
