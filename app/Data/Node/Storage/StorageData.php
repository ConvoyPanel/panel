<?php

namespace App\Data\Node\Storage;

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
        public bool $has_kvm,
        public bool $has_lxc,
        public bool $has_lxc_templates,
        public bool $has_backups,
        public bool $has_iso,
        public bool $has_snippets,
    ) {
    }
}
