<?php

namespace App\Domains\Taxonomy\Repositories\Contracts;

use App\Domains\Taxonomy\Models\MediaCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MediaCategoryRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?MediaCategory;

    public function create(array $payload): MediaCategory;

    public function update(MediaCategory $category, array $payload): MediaCategory;

    public function delete(MediaCategory $category): void;
}
