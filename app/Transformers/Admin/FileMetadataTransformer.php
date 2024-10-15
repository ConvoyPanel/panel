<?php

namespace App\Transformers\Admin;

use App\Data\Node\Storage\FileMetaData;
use League\Fractal\TransformerAbstract;

class FileMetadataTransformer extends TransformerAbstract
{
    public function transform(FileMetaData $data)
    {
        return $data->toArray();
    }
}
