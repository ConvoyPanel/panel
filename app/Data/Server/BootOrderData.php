<?php

namespace App\Data\Server;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

class BootOrderData extends Data
{
    /**
     * Both properties hold DiskData objects, but the collections are deliberately
     * left un-annotated: telling laravel-data they are data collectables makes it
     * wrap each one in its own `data` key, and the client expects bare arrays. The
     * item type is declared for TypeScript instead.
     */
    public function __construct(
        #[LiteralTypeScriptType('App.Data.Server.Proxmox.Config.DiskData[]')]
        public Collection $unusedDevices,
        #[LiteralTypeScriptType('App.Data.Server.Proxmox.Config.DiskData[]')]
        public Collection $bootOrder,
    ) {}
}
