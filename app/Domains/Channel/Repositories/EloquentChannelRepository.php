<?php

namespace App\Domains\Channel\Repositories;

use App\Domains\Channel\Models\Channel;
use App\Domains\Channel\Repositories\Contracts\ChannelRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentChannelRepository implements ChannelRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Channel::query()->orderBy('name')->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?Channel
    {
        return Channel::query()->where('uuid', $uuid)->first();
    }

    public function create(array $payload): Channel
    {
        return Channel::query()->create($payload);
    }

    public function update(Channel $channel, array $payload): Channel
    {
        $channel->update($payload);

        return $channel->refresh();
    }

    public function delete(Channel $channel): void
    {
        $channel->delete();
    }
}
