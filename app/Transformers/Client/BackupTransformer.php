<?php

namespace Convoy\Transformers\Client;

use Convoy\Models\Backup;
use League\Fractal\TransformerAbstract;

class BackupTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     */
    protected array $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include
     */
    protected array $availableIncludes = [
        //
    ];

    /**
     * A Fractal transformer.
     */
    public function transform(Backup $backup): array
    {
        return [
            'uuid' => $backup->uuid,
            // 0/1, as the API sent them from MySQL's tinyint. Postgres returns a real
            // boolean, and integrations may compare with === 1.
            'is_successful' => (int) $backup->is_successful,
            'is_locked' => (int) $backup->is_locked,
            'name' => $backup->name,
            'size' => $backup->size,
            'completed_at' => $backup->completed_at,
            'created_at' => $backup->created_at,
        ];
    }
}
