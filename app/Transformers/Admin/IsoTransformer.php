<?php

namespace App\Transformers\Admin;

use App\Models\ISO;
use League\Fractal\TransformerAbstract;

class IsoTransformer extends TransformerAbstract
{
    public function transform(ISO $iso)
    {
        return [
            'uuid' => $iso->uuid,
            'is_successful' => $iso->is_successful,
            'name' => $iso->name,
            'file_name' => $iso->file_name,
            'size' => $iso->size,
            'hidden' => $iso->hidden,
            'completed_at' => $iso->completed_at,
            'created_at' => $iso->created_at,
        ];
    }
}
