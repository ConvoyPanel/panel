<?php

namespace App\Data\Node\Storage;

use Spatie\LaravelData\Data;

class FileMetaData extends Data
{
    public function __construct(
        public string $fileName,
        public string $mimeType,
        public int $size,
    ) {}
}
