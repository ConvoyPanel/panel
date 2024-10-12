<?php

namespace Convoy\Transformers\Admin;

use Convoy\Data\Node\Storage\FileMetaData;
use League\Fractal\TransformerAbstract;

class FileMetadataTransformer extends TransformerAbstract
{
    public function transform(FileMetaData $data)
    {
        return $data->toArray();
    }
}
