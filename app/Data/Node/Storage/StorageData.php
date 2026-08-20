<?php

namespace App\Data\Node\Storage;

use App\Enums\Node\Storage\StorageContentType;
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
        // One parser for PVE's content list, shared with the poll that records
        // the same flags. It used to be a substring test done here by hand, and
        // it looked for `templates` -- a token PVE does not emit, so no storage
        // ever came back able to hold an LXC template.
        $flags = StorageContentType::flagsFor($raw['content'] ?? null);

        return new self(
            name              : $raw['storage'],
            used              : $raw['used'],
            free              : $raw['avail'],
            total             : $raw['total'],
            enabled           : $raw['enabled'],
            online            : $raw['active'],
            isSharable        : $raw['shared'],
            storesKvm         : $flags['stores_kvm'],
            storesLxc         : $flags['stores_lxc'],
            storesLxcTemplates: $flags['stores_lxc_templates'],
            storesBackups     : $flags['stores_backups'],
            storesIso         : $flags['stores_iso'],
            storesSnippets    : $flags['stores_snippets'],
        );
    }
}
