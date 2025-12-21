<?php

namespace App\Data\Server\Proxmox\GuestAgent;

use Spatie\LaravelData\Data;
use Illuminate\Support\Arr;

class GuestAgentOsInfoData extends Data
{
    public function __construct(
        public ?string $name,
        public ?string $kernelRelease,
        public ?string $version,
        public ?string $prettyName,
        public ?string $versionId,
        public ?string $machine,
        public ?string $id,
        public ?string $kernelVersion,
    ) {}

    public static function fromRaw(array $raw): self
    {
        $data = $raw['result'] ?? $raw;
        $get = fn (string $key) => Arr::get($data, $key);

        return new self(
            name: $get('name'),
            kernelRelease: $get('kernel-release'),
            version: $get('version'),
            prettyName: $get('pretty-name'),
            versionId: $get('version-id'),
            machine: $get('machine'),
            id: $get('id'),
            kernelVersion: $get('kernel-version'),
        );
    }
}
