<?php

namespace App\Domains\Taxonomy\Services;

use App\Domains\Taxonomy\Models\MediaTag;
use App\Domains\Taxonomy\Repositories\Contracts\MediaTagRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class MediaTagService
{
    public function __construct(
        private readonly MediaTagRepositoryInterface $tags,
    ) {
    }

    public function paginate(): LengthAwarePaginator
    {
        return $this->tags->paginate();
    }

    public function findByUuid(string $uuid): ?MediaTag
    {
        return $this->tags->findByUuid($uuid);
    }

    public function create(array $payload): MediaTag
    {
        $payload['uuid'] = (string) Str::uuid();
        $payload['slug'] = $payload['slug'] ?? Str::slug($payload['name']);

        return $this->tags->create($payload);
    }

    public function update(MediaTag $tag, array $payload): MediaTag
    {
        $payload['slug'] = $payload['slug'] ?? Str::slug($payload['name']);

        return $this->tags->update($tag, $payload);
    }

    public function delete(MediaTag $tag): void
    {
        $this->tags->delete($tag);
    }
}
