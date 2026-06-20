<?php

namespace App\Domains\Vmix\DTOs;

class VmixInputDTO
{
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $type,
        public readonly string $state,
    ) {
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'type' => $this->type,
            'state' => $this->state,
        ];
    }
}
