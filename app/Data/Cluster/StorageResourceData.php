<?php

namespace App\Data\Cluster;

use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

/**
 * A datastore row from `/cluster/resources` (`type=storage`).
 *
 * These arrive in the same response the poller already makes for node
 * reachability and guest power state, so per-datastore capacity costs no extra
 * call and no extra timeout on a node that is down. Before this they were
 * decoded and thrown away.
 *
 * Deliberately not `StorageData` (`App\Data\Node\Storage`): that one is the
 * live `/nodes/{node}/storage` shape, carries the content-type flags the
 * storage manager needs, and is fetched per request. This is the subset
 * `/cluster/resources` actually returns.
 */
class StorageResourceData extends Data
{
    public function __construct(
        /** The PVE storage id, e.g. `local-lvm`. */
        public readonly string $name,
        /** The PVE node this row was reported for -- a cluster answers for all of them. */
        public readonly string $nodeName,
        public readonly int $used,
        public readonly int $total,
        /** PVE's own word for it: `available`, or `unknown` when it cannot tell. */
        public readonly string $status,
        /**
         * Shared storage is reported once per node, so the same SAN counts
         * against every host that mounts it. Marked so the UI can say so rather
         * than implying each node has its own copy.
         */
        public readonly bool $shared,
        /**
         * The PVE backend: `dir`, `lvmthin`, `zfspool`, `nfs`, `rbd`, `pbs`, ...
         *
         * PVE calls it `plugintype`. It is what separates a thin backend, where
         * committed legitimately exceeds physical bytes, from a thick one where
         * the same gap means something is wrong -- and it is the only thing that
         * identifies a Proxmox Backup Server datastore, which deduplicates and so
         * behaves like a thin backend no matter what is written to it.
         */
        public readonly ?string $type = null,
        /** PVE's comma-separated content list: `images,rootdir`, `backup`, ... */
        public readonly ?string $content = null,
    ) {}

    /** Backends where written bytes are always below the sum of provisioned sizes. */
    private const THIN_TYPES = ['lvmthin', 'zfspool', 'zfs', 'rbd', 'pbs', 'btrfs'];

    /**
     * Whether Convoy's committed total may legitimately exceed physical usage.
     *
     * True for thin backends and for PBS, which dedups chunk-wise across every
     * namespace and cluster pointed at it. Callers use this to decide whether
     * `physical - committed` is a meaningful subtraction; on these it is not, and
     * presenting the difference as unaccounted space would be a fabrication.
     */
    public function isThin(): bool
    {
        return $this->type !== null && in_array($this->type, self::THIN_TYPES, true);
    }

    public static function fromRaw(array $raw): self
    {
        return new self(
            name: Arr::get($raw, 'storage', ''),
            nodeName: Arr::get($raw, 'node', ''),
            // `disk`/`maxdisk` mean used/total bytes for storage rows, where for
            // a guest row they would mean its root image. Same keys, different
            // question -- which is why these are only read off `type=storage`.
            used: (int) Arr::get($raw, 'disk', 0),
            total: (int) Arr::get($raw, 'maxdisk', 0),
            status: (string) Arr::get($raw, 'status', 'unknown'),
            shared: (bool) Arr::get($raw, 'shared', false),
            type: Arr::get($raw, 'plugintype'),
            content: Arr::get($raw, 'content'),
        );
    }
}
