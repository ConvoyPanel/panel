<?php

namespace App\Data\Storage;

use Spatie\LaravelData\Data;

/**
 * One thing occupying space on a storage.
 *
 * Servers, backups and ISOs are different resources with different lifecycles,
 * but on this screen they are the same question -- what is taking the room, and
 * may I remove it -- so they share a shape rather than each getting their own.
 */
class StorageConsumerData extends Data
{
    public function __construct(
        public int $id,
        /**
         * What the delete route binds on. Every one of these models inherits
         * `uuid` as its route key, so the integer id above is for React lists and
         * this is for the API.
         */
        public string $routeKey,
        /** The node to address an ISO through; its delete route is node-scoped. */
        public ?int $nodeId,
        public string $name,
        /** Bytes. */
        public int $size,
        /** Who to ask before deleting it, where that is a person. */
        public ?string $owner,
        /** Context the row needs to be actionable: a vmid, a server name, a date. */
        public ?string $detail,
        /**
         * Whether Convoy will let this be deleted from here.
         *
         * A locked backup says no, and saying so on the row is better than
         * offering a button that fails.
         */
        public bool $deletable,
    ) {}
}
