<?php

namespace App\Domains\Taxonomy\Services;

use App\Domains\Taxonomy\Models\MediaCategory;
use App\Domains\Taxonomy\Repositories\Contracts\MediaCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class MediaCategoryService
{
    public function __construct(
        private readonly MediaCategoryRepositoryInterface $categories,
    ) {
    }

    public function paginate(): LengthAwarePaginator
    {
        return $this->categories->paginate();
    }

    public function findByUuid(string $uuid): ?MediaCategory
    {
        return $this->categories->findByUuid($uuid);
    }

    public function create(array $payload): MediaCategory
    {
        $payload['uuid'] = (string) Str::uuid();
        $payload['slug'] = $payload['slug'] ?? Str::slug($payload['name']);

        return $this->categories->create($payload);
    }

    public function update(MediaCategory $category, array $payload): MediaCategory
    {
        $payload['slug'] = $payload['slug'] ?? Str::slug($payload['name']);

        return $this->categories->update($category, $payload);
    }

    public function delete(MediaCategory $category): void
    {
        $this->categories->delete($category);
    }
}
