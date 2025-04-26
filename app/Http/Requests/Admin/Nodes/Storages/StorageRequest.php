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

        // Override display_name validation based on is_shareable
        if ($this->boolean('is_shareable')) {
            $rules['display_name'] = 'required|string|min:1|max:40';
        } else {
            $rules['display_name'] = 'nullable|string|max:40';
        }

        return $rules;
    }
}
