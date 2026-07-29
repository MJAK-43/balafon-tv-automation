<?php

namespace App\Domains\Channel\Repositories\Contracts;

use App\Domains\Channel\Models\Channel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ChannelRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?Channel;

    public function create(array $payload): Channel;

    public function update(Channel $channel, array $payload): Channel;

    public function delete(Channel $channel): void;
}
