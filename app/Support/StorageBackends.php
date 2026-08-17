<?php

namespace App\Support;

/**
 * What Convoy needs to know about a Proxmox storage backend.
 *
 * PVE's set of backends is open-ended and installs can carry plugins Convoy has
 * never heard of, so this answers questions about a type string rather than
 * enumerating one -- an unknown backend gets the conservative answer instead of
 * an exception.
 */
final class StorageBackends
{
    /**
     * Backends where written bytes are routinely below the sum of provisioned sizes.
     *
     * Thin backends allocate on write, so a 1 TiB disk that has written 40 GiB
     * costs 40 GiB. `pbs` is here for a different reason with the same effect:
     * a Proxmox Backup Server datastore deduplicates chunk-wise across every
     * namespace and cluster pointed at it, so its physical figure bears no
     * arithmetic relationship to the backups it holds.
     */
    private const THIN = ['lvmthin', 'zfspool', 'zfs', 'rbd', 'pbs', 'btrfs', 'cephfs'];

    /**
     * Whether Convoy's committed total may legitimately exceed physical usage.
     *
     * This is the question that decides whether `physical - committed` means
     * anything. On a thin backend the difference is normal operation and
     * presenting it as unaccounted space would be a fabrication; on a thick one
     * it is genuinely storage Convoy cannot explain.
     *
     * Unknown backends answer false: reporting a real gap that turns out to be
     * thin provisioning is a smaller error than silently hiding one.
     */
    public static function isThin(?string $type): bool
    {
        return $type !== null && in_array($type, self::THIN, true);
    }
}
