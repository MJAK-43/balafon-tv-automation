<?php

namespace App\Domains\Taxonomy\Repositories\Contracts;

use App\Domains\Taxonomy\Models\MediaTag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MediaTagRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?MediaTag;

    public function create(array $payload): MediaTag;

    public function update(MediaTag $tag, array $payload): MediaTag;

    public function delete(MediaTag $tag): void;
}
