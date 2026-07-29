<?php

namespace App\Domains\Channel\Services;

use App\Domains\Channel\Models\Channel;
use App\Domains\Channel\Repositories\Contracts\ChannelRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ChannelService
{
    public function __construct(
        private readonly ChannelRepositoryInterface $channels,
    ) {
    }

    public function paginate(): LengthAwarePaginator
    {
        $this->ensureDefaultChannel();

        return $this->channels->paginate();
    }

    private function ensureDefaultChannel(): void
    {
        if (Channel::query()->exists()) {
            return;
        }

        Channel::query()->firstOrCreate(
            ['code' => 'BTV'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Balafon TV',
                'timezone' => config('app.timezone', 'Europe/Paris'),
                'description' => 'Default broadcast channel',
                'status' => 'active',
            ],
        );
    }

    public function findByUuid(string $uuid): ?Channel
    {
        return $this->channels->findByUuid($uuid);
    }

    public function create(array $payload): Channel
    {
        $payload['uuid'] = (string) Str::uuid();

        return $this->channels->create($payload);
    }

    public function update(Channel $channel, array $payload): Channel
    {
        return $this->channels->update($channel, $payload);
    }

    public function delete(Channel $channel): void
    {
        $this->channels->delete($channel);
    }
}
