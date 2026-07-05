<?php

namespace App\Rules;

use App\Models\Node;
use App\Models\Storage;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class HasSufficientDiskSpace implements DataAwareRule, ValidationRule
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
        $storageId = (int) $storageId;

        $node = Node::find($nodeId);
        if (! $node) {
            return;
        }

        $storage = Storage::query()
            ->whereKey($storageId)
            ->whereHas('nodes', fn ($query) => $query->whereKey($node->id))
            ->first();
        if (! $storage instanceof Storage) {
            return;
        }

        $used = $storage->server_usage + $storage->backup_usage + $storage->iso_usage;

        if ($value > ($storage->size - $used)) {
            $fail('The storage location does not have enough disk space available.');
        }
    }
}
