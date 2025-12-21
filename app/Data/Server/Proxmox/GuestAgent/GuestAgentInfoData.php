<?php

namespace App\Data\Server\Proxmox\GuestAgent;

use Spatie\LaravelData\Data;
use Illuminate\Support\Arr;

class GuestAgentInfoData extends Data
{
    public function __construct(
        public string $version,
        public array $supportedCommands,
    ) {}

    public static function fromRaw(array $raw): self
    {
        // The data is usually nested in 'result'
        $data = $raw['result'] ?? $raw;

        return new self(
            version: Arr::get($data, 'version', ''),
            supportedCommands: Arr::get($data, 'supported_commands', []),
        );
    }
}
