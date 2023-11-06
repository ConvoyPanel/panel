<?php

namespace Convoy\Transformers\Admin;

use League\Fractal\TransformerAbstract;
use Convoy\Data\Node\Storage\FileMetaData;

class FileMetadataTransformer extends TransformerAbstract
{
    public function transform(FileMetaData $data)
    {
        return $data->toArray();
    }
}
