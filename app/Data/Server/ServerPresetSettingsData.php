<?php

namespace App\Data\Server;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The saved half of the admin server-create form.
 *
 * Every field is nullable, and that is the whole design: a preset answers the
 * questions an admin repeats and stays silent on the rest, so applying one
 * fills the fields it names and leaves the others as the operator left them.
 *
 * Units are the *form's* units, not the database's — memory and disk in MiB,
 * each extra disk in GiB, the NIC cap in MB/s. A preset is a set of keystrokes
 * saved for later, so the byte conversions stay where they already happen, at
 * submit time.
 *
 * Deliberately absent: name, hostname, VMID, owner and the account password.
 * The first four are what makes a server that server, and a password does not
 * belong in a record meant to be reused and shared.
 */
#[MapInputName(SnakeCaseMapper::class)]
class ServerPresetSettingsData extends Data
{
    public function __construct(
        /**
         * Node-scoped choices only mean anything alongside the node they were
         * made on, so the request refuses a storage, interface or extra disk
         * without a node — see ServerPresetRequest.
         */
        public ?int $nodeId = null,
        public ?int $storageId = null,
        public ?int $cpu = null,
        /** Mebibytes. */
        public ?int $memory = null,
        /** Mebibytes. */
        public ?int $disk = null,
        /** Mebibytes. */
        public ?int $bandwidth = null,
        /** Megabytes per second, per NIC. Null leaves the NIC uncapped. */
        public ?float $speedLimit = null,
        /** -1 for unlimited, matching the create form. */
        public ?int $backupCount = null,
        /** Mebibytes; -1 for unlimited. */
        public ?int $backupSize = null,
        /**
         * Extra data disks, each `{ storageId, size }` with the size in GiB.
         *
         * The attribute is load-bearing, not decoration: the docblock alone
         * leaves Spatie treating this as a plain array, and a preset saved with
         * extra disks comes back out with `disks: null`.
         *
         * @var ServerPresetDiskData[]|null
         */
        #[DataCollectionOf(ServerPresetDiskData::class)]
        public ?array $disks = null,
        public ?int $networkInterfaceId = null,
        public ?int $vlanTag = null,
        public ?int $addressesIpv4Count = null,
        public ?int $addressesIpv6Count = null,
        public ?bool $deferredOsSelection = null,
        public ?bool $shouldCreateVm = null,
        /**
         * The template's group, which the form needs to repopulate its template
         * picker — the picker lists by group, so restoring the template alone
         * would leave it showing an empty list next to a chosen OS.
         */
        public ?string $templateGroupUuid = null,
        public ?string $templateUuid = null,
        public ?bool $startOnCompletion = null,
    ) {}
}
