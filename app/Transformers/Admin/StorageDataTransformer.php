<?php

namespace App\Transformers\Admin;

use App\Data\Node\Storage\StorageData;
use League\Fractal\TransformerAbstract;
use Spatie\LaravelData\DataCollection;

class StorageDataTransformer extends TransformerAbstract
{
    /**
     * @param  DataCollection<int, StorageData>|StorageData  $data
     */
    public function transform(DataCollection|StorageData $data): array
    {
        return $data->toArray();
    }
}
