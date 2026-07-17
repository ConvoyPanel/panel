<?php

namespace App\Enums\Server\Disk;

// Backed so it transforms to a real TypeScript enum. A pure enum has no value
// for the transformer to emit, which surfaced as `diskMediaType: undefined` in
// the generated types. Nothing depends on the backing values (PHP compares enum
// cases by identity; the frontend does not read this field), so introducing them
// is safe.
enum DiskMediaType: string
{
    case DISK = 'disk';
    case CDROM = 'cdrom';
}
