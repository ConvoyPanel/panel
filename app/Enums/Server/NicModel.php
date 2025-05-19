<?php

namespace App\Enums\Server;

use Illuminate\Support\Arr;

/**
 * All supported NIC models for Proxmox.
 *
 * @see https://pve.proxmox.com/pve-docs/qm.1.html#qm_virtio
 * @see https://pve.proxmox.com/pve-docs/api-viewer/#/nodes/%7Bnode%7D/qemu/%7Bvmid%7D/config
 */
enum NicModel: string
{
    case E1000 = 'e1000';
    case E1000_82540EM = 'e1000-82540em';
    case E1000_82544GC = 'e1000-82544gc';
    case E1000_82545EM = 'e1000-82545em';
    case E1000E = 'e1000e';
    case I82551 = 'i82551';
    case I82557B = 'i82557b';
    case I82559ER = 'i82559er';
    case NE2K_ISA = 'ne2k_isa';
    case NE2K_PCI = 'ne2k_pci';
    case PCNET = 'pcnet';
    case RTL8139 = 'rtl8139';
    case VIRTIO = 'virtio';
    case VMXNET3 = 'vmxnet3';

    /**
     * Get all NIC models
     */
    public static function values(): array
    {
        return Arr::map(self::cases(), fn ($case) => $case->value);
    }
}
