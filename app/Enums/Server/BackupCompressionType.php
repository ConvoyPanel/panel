<?php

namespace App\Enums\Server;

enum BackupCompressionType: string
{
    case NONE = 'none';
    case LZO = 'lzo';
    case GZIP = 'gzip';
    case ZSTD = 'zstd';
}
