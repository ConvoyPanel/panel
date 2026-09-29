<?php

namespace App\Data\Server\Adoption;

use Spatie\LaravelData\Data;

/**
 * Everything read off a guest before anyone decides to adopt it.
 *
 * Read-only in the strongest sense: producing this makes exactly two PVE
 * requests, both GETs, and writes nothing to the guest or to the database.
 */
class GuestAdoptionPreviewData extends Data
{
    public function __construct(
        public readonly int $nodeId,
        public readonly string $nodeName,
        public readonly int $vmid,
        public readonly ?string $name,
        /** `cores`, the column `servers.cpu` mirrors. Sockets are not modelled. */
        public readonly int $cpuCount,
        public readonly int $memory,
        public readonly int $diskSize,
        /** The bridge `net0` sits on, as the guest's config gives it. */
        public readonly ?string $bridge,
        public readonly ?string $macAddress,
        public readonly ?int $vlanTag,
        /** The Convoy interface row matching that bridge on this node, if there is one. */
        public readonly ?int $networkInterfaceId,
        /** The Convoy storage row the primary disk lives on, if there is one. */
        public readonly ?int $storageId,
        public readonly ?string $storageName,
        /** @var array<int, AdoptionAddressData> from `ipconfig0` only */
        public readonly array $addresses,
        /**
         * True when `ipconfig0` says `dhcp` / `ip6=auto`, or is absent. The
         * server is adopted with no addresses and `ipconfig_managed = false`,
         * so nothing later overwrites what the guest is actually using.
         */
        public readonly bool $hasUnmanagedIpConfig,
        /**
         * NICs past `net0`. Read and reported, never reconciled: one NIC is the
         * scope line, and a second one silently ignored is worse than a second
         * one named.
         *
         * @var array<int, string>
         */
        public readonly array $ignoredInterfaces,
        /** Why this guest cannot be adopted at all, or null. */
        public readonly ?string $blockedReason,
    ) {}
}
