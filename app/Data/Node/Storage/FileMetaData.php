<?php

namespace App\Data\Node\Storage;

use Spatie\LaravelData\Data;

class FileMetaData extends Data
{
    public function __construct(
        public string $file_name,
        public string $mime_type,
        public int    $size,
    ) {
    }
}
