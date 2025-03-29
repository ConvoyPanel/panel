<?php

namespace App\Data\Node\Storage;

use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

class StorageData extends Data
{
    public function __construct(
        public string $name,
        public int $used,
        public int $free,
        public int $total,
        public bool $enabled,
        public bool $online,
        public bool $isSharable,
        public bool $storesKvm,
        public bool $storesLxc,
        public bool $storesLxcTemplates,
        public bool $storesBackups,
        public bool $storesIso,
        public bool $storesSnippets,
    ) {}

    public static function fromRaw(array $raw): self
    {
        $stores = fn (string $content) => Str::contains($raw['content'], $content);

        return new self(
            name              : $raw['storage'],
            used              : $raw['used'],
            free              : $raw['avail'],
            total             : $raw['total'],
            enabled           : $raw['enabled'],
            online            : $raw['active'],
            isSharable        : $raw['shared'],
            storesKvm         : $stores('images'),
            storesLxc         : $stores('rootdir'),
            storesLxcTemplates: $stores('templates'),
            storesBackups     : $stores('backup'),
            storesIso         : $stores('iso'),
            storesSnippets    : $stores('snippets'),
        );
    }
}
