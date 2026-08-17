<?php

namespace App\Data\Server;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * Everything the Storage screen needs about a server's block devices, from the
 * single PVE config read that already backs both halves.
 *
 * Boot order is expressed as an ordering *over* `$devices` rather than as a
 * second list of devices: a device belongs to the server, and its boot position
 * is a fact about that device. Two parallel collections (the shape this
 * replaces) let the same device exist in one of two places depending on state,
 * which is what made "remove from boot order" read as "delete the disk".
 */
class ServerStorageData extends Data
{
    /**
     * The collection is deliberately un-annotated as a data collectable -- doing
     * so wraps each item in its own `data` key, and the client expects a bare
     * array. The item type is declared for TypeScript instead. Same reasoning as
     * the two-collection `BootOrderData` it supersedes.
     *
     * @param  string[]  $bootOrder  interfaces, in the order they are tried
     */
    public function __construct(
        #[LiteralTypeScriptType('App.Data.Server.StorageDeviceData[]')]
        public Collection $devices,
        /** @var string[] */
        public array $bootOrder,
    ) {}
}
