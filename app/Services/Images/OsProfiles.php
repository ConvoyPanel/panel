<?php

namespace App\Services\Images;

/**
 * The hardware an image gets when nobody has said otherwise.
 *
 * A definition's stored `hardware` is an overlay, not a specification: it names
 * the keys that differ and stays silent on the rest. That is the whole reason
 * the "bring your own image" form can ask one question -- pick an OS -- and
 * still produce a complete `qm create`. Without a default underneath it, every
 * uploaded disk would need an admin to answer questions they have no way to
 * answer, and a wrong `scsihw` is not a validation error, it is
 * `0x7B INACCESSIBLE_BOOT_DEVICE` at first power-on.
 *
 * These are deliberately the same handful of settings a cofoundry sidecar would
 * have written, so an ingested image and a hand-added one agree by default.
 */
class OsProfiles
{
    /**
     * Keys the create call interprets rather than forwarding to Proxmox.
     *
     * They name slots, which cannot be stored as finished `qm` arguments because
     * the storage a server lands on is not known until it is being built.
     */
    public const META_KEYS = ['boot_disk_slot', 'cloudinit_slot'];

    /**
     * Shared by every guest. `cpu: host` is the right baseline -- the Windows
     * Server 2025 installer probes for SSE4.1/4.2 -- at the cost of blocking
     * live migration between heterogeneous nodes, which is an override an
     * operator makes deliberately rather than a surprise.
     */
    private const COMMON = [
        'boot_disk_slot' => 'scsi0',
        'cloudinit_slot' => 'ide2',
        'machine' => 'q35',
        'scsihw' => 'virtio-scsi-single',
        'cpu' => 'host',
        'agent' => '1',
    ];

    public static function isWindows(string $ostype): bool
    {
        return str_starts_with($ostype, 'win')
            || str_starts_with($ostype, 'w2k')
            || in_array($ostype, ['wvista', 'wxp'], true);
    }

    /**
     * `machine` is never a version-pinned value like `pc-q35-11.0`: a pin names
     * the QEMU of whichever machine built the image and hard-fails on a node
     * running an older one.
     */
    public static function defaults(string $ostype): array
    {
        return array_merge(self::COMMON, [
            // Windows images ship as OVMF with a varstore beside them; anything
            // else boots fine on SeaBIOS and does not need one.
            'bios' => self::isWindows($ostype) ? 'ovmf' : 'seabios',
        ]);
    }

    /**
     * An unset key inherits rather than clears, which is what makes the admin
     * form safe to show in full while asking for none of it. A key set to null
     * is treated as "not set" for the same reason.
     */
    public static function merge(string $ostype, array $overlay): array
    {
        return array_merge(
            self::defaults($ostype),
            array_filter($overlay, fn ($value) => ! is_null($value)),
        );
    }

    /**
     * The overlay with the meta keys stripped -- what actually reaches Proxmox.
     */
    public static function proxmoxKeys(array $hardware): array
    {
        return array_diff_key($hardware, array_flip(self::META_KEYS));
    }
}
