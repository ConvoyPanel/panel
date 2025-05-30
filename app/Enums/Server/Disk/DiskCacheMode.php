<?php

namespace App\Enums\Server\Disk;

enum DiskCacheMode: string
{
    case NONE = 'none';

    case WRITEBACK = 'writeback';

    case WRITETHROUGH = 'writethrough';

    case DIRECTSYNC = 'directsync';

    case UNSAFE = 'unsafe';
}
