<?php

namespace App\Domains\Vmix\DTOs;

class VmixStatusDTO
{
    /**
     * @param  array<int, VmixInputDTO>  $inputs
     */
    public function __construct(
        public readonly bool $connected,
        public readonly ?string $version,
        public readonly ?string $edition,
        public readonly ?string $activeInput,
        public readonly ?string $previewInput,
        public readonly bool $streaming,
        public readonly bool $recording,
        public readonly bool $external,
        public readonly bool $fullscreen,
        public readonly array $inputs,
        public readonly array $raw = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'connected' => $this->connected,
            'version' => $this->version,
            'edition' => $this->edition,
            'active_input' => $this->activeInput,
            'preview_input' => $this->previewInput,
            'streaming' => $this->streaming,
            'recording' => $this->recording,
            'external' => $this->external,
            'fullscreen' => $this->fullscreen,
            'inputs_count' => count($this->inputs),
            'inputs' => array_map(fn (VmixInputDTO $input): array => $input->toArray(), $this->inputs),
            'raw' => $this->raw,
        ];
    }
}
