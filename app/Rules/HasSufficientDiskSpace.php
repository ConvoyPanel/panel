<?php

namespace App\Rules;

use App\Models\Node;
use App\Models\Storage;
use App\Services\Nodes\LiveStorageService;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

use function max;

class HasSufficientDiskSpace implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Reject an allocation that exceeds what Convoy may actually consume on the
     * target storage. Capacity comes from live Proxmox (the truth — includes
     * the base system and any non-Convoy usage), minus the operator's reserve
     * buffer: freeForConvoy = physicalFree − reservedBytes.
     *
     * Fails open when the node is unreachable — a transient outage must not
     * block server creation (mirrors the storage listing's offline fallback).
     */
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

        $live = app(LiveStorageService::class)->get($node, $storage->name);
        if ($live === null) {
            // Node offline / storage not reported — fail open.
            return;
        }

        $freeForConvoy = max(0, $live->free - (int) ($storage->reserved_bytes ?? 0));

        if ($value > $freeForConvoy) {
            $fail('The storage location does not have enough disk space available.');
        }
    }
}
