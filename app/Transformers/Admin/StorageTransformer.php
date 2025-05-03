<?php

namespace App\Transformers\Admin;

use App\Models\Storage;
use League\Fractal\TransformerAbstract;

class StorageTransformer extends TransformerAbstract
{
    public function transform(Storage $storage): array
    {
        return [
            'id' => $storage->id,
            'display_name' => $storage->display_name,
            'description' => $storage->description,
            'name' => $storage->name,
            'size' => $storage->size,
            'is_shareable' => $storage->is_shareable,
            'stores_kvm' => $storage->stores_kvm,
            'stores_lxc' => $storage->stores_lxc,
            'stores_lxc_templates' => $storage->stores_lxc_templates,
            'stores_backups' => $storage->stores_backups,
            'stores_iso' => $storage->stores_iso,
            'stores_snippets' => $storage->stores_snippets,
            'backup_order' => $storage?->pivot?->backup_order,
            'server_usage' => $storage->server_usage,
            'backup_usage' => $storage->backup_usage,
            'iso_usage' => $storage->iso_usage,
            'snapshot_usage' => $storage->snapshot_usage,
        ];
    }
}
