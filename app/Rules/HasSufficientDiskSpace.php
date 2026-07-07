<?php

namespace App\Rules;

use App\Models\Node;
use App\Models\Storage;
use App\Services\Nodes\LiveStorageService;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

/**
 * Aggregate disk-capacity check for a server-create request. Sums *all* the
 * disks a request wants on each storage — the primary (`storage_id` +
 * `limits.disk`) and every secondary (`limits.disks[]`) — and rejects if any
 * storage's total exceeds what Convoy may actually consume there:
 * `freeForConvoy = live physical free − reserve buffer` (the truth, incl. the
 * base system). Summing per storage is why this is one aggregate rule rather
 * than a per-field one: two disks on the same storage must be counted together.
 *
 * Fails open per storage when the node is unreachable — a transient outage must
 * not block creation (mirrors the storage listing's offline fallback).
 */
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

        $node = Node::find($nodeId);
        if (! $node) {
            return;
        }

        // Total requested bytes per storage id: primary + each secondary.
        $requestedByStorage = [];

        $primaryStorageId = $this->data['storage_id'] ?? null;
        $primarySize = Arr::get($this->data, 'limits.disk');
        if (! is_null($primaryStorageId) && ! is_null($primarySize)) {
            $requestedByStorage[(int) $primaryStorageId] = (int) $primarySize;
        }

        foreach (Arr::get($this->data, 'limits.disks', []) as $disk) {
            $storageId = $disk['storage_id'] ?? null;
            $size = $disk['size'] ?? null;
            if (is_null($storageId) || is_null($size)) {
                continue;
            }
            $requestedByStorage[(int) $storageId] = ($requestedByStorage[(int) $storageId] ?? 0) + (int) $size;
        }

        $liveStorage = app(LiveStorageService::class);

        foreach ($requestedByStorage as $storageId => $requested) {
            $storage = Storage::query()
                ->whereKey($storageId)
                ->whereHas('nodes', fn ($query) => $query->whereKey($node->id))
                ->first();
            if (! $storage instanceof Storage) {
                continue;
            }

            $freeForConvoy = $liveStorage->freeForConvoy($node, $storage);
            if ($freeForConvoy === null) {
                // Node offline / storage not reported — fail open.
                continue;
            }

            if ($requested > $freeForConvoy) {
                $fail("The storage \"{$storage->name}\" does not have enough disk space available.");
            }
        }
    }
}
