<?php

namespace App\Data\Server\Proxmox\Activity;

use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

class TaskLogData extends Data
{
    public function __construct(
        public int $lineNumber,
        public string $text,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            lineNumber: (int) Arr::get($raw, 'n'),
            text: (string) Arr::get($raw, 't'),
        );
    }
}

