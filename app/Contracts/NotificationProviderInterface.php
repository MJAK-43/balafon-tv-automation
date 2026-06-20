<?php

namespace App\Contracts;

interface NotificationProviderInterface
{
    public function send(string $channel, array $payload): void;
}
