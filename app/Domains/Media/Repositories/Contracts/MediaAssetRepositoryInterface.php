<?php

namespace App\Domains\Media\Repositories\Contracts;

use App\Domains\Media\Models\MediaAsset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MediaAssetRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function list(array $filters = []): Collection;

    public function findByUuid(string $uuid): ?MediaAsset;

    public function findById(int $id): ?MediaAsset;

    public function create(array $payload): MediaAsset;

    public function update(MediaAsset $mediaAsset, array $payload): MediaAsset;

    public function delete(MediaAsset $mediaAsset): void;
}
