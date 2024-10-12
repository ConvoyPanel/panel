<?php

namespace App\Transformers\Admin;

use League\Fractal\TransformerAbstract;
use App\Data\Node\Storage\FileMetaData;

class FileMetadataTransformer extends TransformerAbstract
{
    public function transform(FileMetaData $data)
    {
        return $data->toArray();
    }
}
