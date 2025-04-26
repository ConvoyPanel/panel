<?php

namespace App\Enums\Node\Storage;

/**
 * Defines the types of content that Proxmox storages can handle.
 */
enum StorageContentType
{
    // Enum Cases representing the content types
    case KVM;
    case LXC;
    case LXC_TEMPLATES;
    case BACKUPS;
    case ISO;
    case SNIPPETS;

    /**
     * Convert the enum case to its corresponding Proxmox storage content type string.
     *
     * @return string The Proxmox content type string (e.g., 'images', 'backup').
     */
    public function toProxmoxString(): string
    {
        return match ($this) {
            self::KVM => 'images', // KVM disk images
            self::LXC => 'rootdir', // LXC container data (root directory)
            self::LXC_TEMPLATES => 'vztmpl', // LXC templates
            self::BACKUPS => 'backup', // Backup files
            self::ISO => 'iso', // ISO image files
            self::SNIPPETS => 'snippets', // Snippet files (e.g., cloud-init configs)
        };
    }

    /**
     * Get the corresponding attribute name on the App\Models\Storage model.
     * This is needed for validation rules that check the model's capabilities.
     *
     * @return string The corresponding boolean attribute name (e.g., 'stores_kvm').
     */
    public function toModelAttributeName(): string
    {
        return match ($this) {
            self::KVM => 'stores_kvm',
            self::LXC => 'stores_lxc',
            self::LXC_TEMPLATES => 'stores_lxc_templates',
            self::BACKUPS => 'stores_backups',
            self::ISO => 'stores_iso',
            self::SNIPPETS => 'stores_snippets',
        };
    }
}
