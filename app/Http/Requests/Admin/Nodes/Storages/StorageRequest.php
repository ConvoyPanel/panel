<?php

namespace App\Http\Requests\Admin\Nodes\Storages;

use App\Http\Requests\BaseApiRequest;
use App\Models\Node;
use App\Models\Storage;
use App\Rules\UniqueStorageNamePerNode;

class StorageRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = Storage::getRules();

        /** @var Node $node */
        $node = $this->parameter('node', Node::class);

        $storageId = null;

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            /** @var Storage $storage */
            $storage = $this->parameter('storage', Storage::class);
            // Set the ID to ignore for the uniqueness check
            $storageId = $storage->id;
        }
        $rules['name'][] = new UniqueStorageNamePerNode($node->id, $storageId);

        // Always optional. This used to be required when the operator ticked
        // "shareable", but that flag is gone -- whether a storage is shared is
        // Proxmox's answer (`pve_shared`), and it is not known at registration
        // because nothing has polled the node yet.
        $rules['display_name'] = 'nullable|string|max:40';

        return $rules;
    }
}
