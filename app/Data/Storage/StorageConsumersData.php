<?php

namespace App\Data\Storage;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Everything occupying a storage, grouped by what it is.
 *
 * Each collection is built `->withoutWrapping()`. Nested inside a `Data` that is
 * itself returned from a controller, a collection otherwise picks up the global
 * `data` wrapper a second time and the payload arrives as `servers.data[]` while
 * the generated TypeScript says `StorageConsumerData[]`.
 */
class StorageConsumersData extends Data
{
    public function __construct(
        /** @var DataCollection<int, StorageConsumerData> */
        public DataCollection $servers,
        /** @var DataCollection<int, StorageConsumerData> */
        public DataCollection $backups,
        /** @var DataCollection<int, StorageConsumerData> */
        public DataCollection $isos,
    ) {}
}
