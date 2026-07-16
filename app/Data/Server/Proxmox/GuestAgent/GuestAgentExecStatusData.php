<?php

namespace App\Data\Server\Proxmox\GuestAgent;

use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

class GuestAgentExecStatusData extends Data
{
    public function __construct(
        public bool $exited,
        public ?int $exitCode,
        public ?string $outData,
        public ?string $errData,
        public bool $outTruncated,
        public bool $errTruncated,
        public ?int $signal,
    ) {}

    public static function fromRaw(array $raw): self
    {
        $data = $raw['result'] ?? $raw;
        $get = fn (string $key, $default = null) => Arr::get($data, $key, $default);

        return new self(
            exited: (bool) $get('exited', false),
            exitCode: $get('exitcode'),
            outData: $get('out-data'),
            errData: $get('err-data'),
            outTruncated: (bool) $get('out-truncated', false),
            errTruncated: (bool) $get('err-truncated', false),
            signal: $get('signal'),
        );
    }
}
