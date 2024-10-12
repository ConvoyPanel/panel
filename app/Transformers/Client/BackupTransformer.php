<?php

namespace App\Transformers\Client;

use App\Models\Backup;
use League\Fractal\TransformerAbstract;

class BackupTransformer extends TransformerAbstract
{
    public function transform(Backup $backup): array
    {
        return [
            'id' => $backup->id,
            'uuid' => $backup->uuid,
            'server_id' => $backup->server_id,
            'storage_id' => $backup->storage_id,
            'name' => $backup->name,
            'description' => $backup->description,
            'is_locked' => $backup->is_locked,
            'errors' => $backup->errors,
            'file_name' => $backup->file_name,
            'size' => $backup->size,
            'completed_at' => $backup->completed_at,
            'created_at' => $backup->created_at,
        ];
    }
}
