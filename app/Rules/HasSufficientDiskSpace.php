<?php

namespace App\Rules;

use App\Models\Node;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class HasSufficientDiskSpace implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nodeId = $this->data['node_id'] ?? null;
        if (is_null($nodeId)) {
            return;
        }

        $storageId = $this->data['storage_id'] ?? null;
        if (is_null($storageId)) {
            return;
        }

        $node = Node::find($nodeId);
        if (!$node) {
            return;
        }

        $storage = $node->storages()->find($storageId);
        if (!$storage) {
            return;
        }

        if ($value > ($storage->disk - $storage->disk_used)) {
            $fail('The storage location does not have enough disk space available.');
        }
    }
}