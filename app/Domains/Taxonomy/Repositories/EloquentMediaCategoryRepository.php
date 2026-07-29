<?php

namespace App\Domains\Taxonomy\Repositories;

use App\Domains\Taxonomy\Models\MediaCategory;
use App\Domains\Taxonomy\Repositories\Contracts\MediaCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentMediaCategoryRepository implements MediaCategoryRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return MediaCategory::query()->orderBy('name')->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?MediaCategory
    {
        return MediaCategory::query()->where('uuid', $uuid)->first();
    }

    public function create(array $payload): MediaCategory
    {
        return MediaCategory::query()->create($payload);
    }

    public function update(MediaCategory $category, array $payload): MediaCategory
    {
        $category->update($payload);

        return $category->refresh();
    }

    public function delete(MediaCategory $category): void
    {
        $category->delete();
    }
}
