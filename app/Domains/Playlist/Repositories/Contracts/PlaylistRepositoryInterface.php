<?php

namespace App\Domains\Playlist\Repositories\Contracts;

use App\Domains\Playlist\Models\Playlist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlaylistRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?Playlist;

    public function findById(int $id): ?Playlist;

    public function create(array $payload): Playlist;

    public function update(Playlist $playlist, array $payload): Playlist;

    public function delete(Playlist $playlist): void;
}
