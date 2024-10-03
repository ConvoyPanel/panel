<?php

namespace Convoy\Transformers\Client;

use Convoy\Models\Snapshot;
use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;

class SnapshotTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'children',
    ];

    public function transform(Snapshot $snapshot): array
    {
        return [
            'id' => $snapshot->id,
            'uuid' => $snapshot->uuid,
            'server_id' => $snapshot->server_id,
            'snapshot_id' => $snapshot->snapshot_id,
            'name' => $snapshot->name,
            'description' => $snapshot->description,
            'is_locked' => $snapshot->is_locked,
            'errors' => $snapshot->errors,
            'size' => $snapshot->size,
            'completed_at' => $snapshot->completed_at,
            'created_at' => $snapshot->created_at,
        ];
    }

    public function includeChildren(Snapshot $snapshot): Collection
    {
        return $this->collection($snapshot->children, new self());
    }
}
