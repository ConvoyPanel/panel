<?php

namespace App\Enums\Server\Disk;

enum DiskFormat: string
{
    case RAW = 'raw';

    case QCOW = 'qcow';

    case QED = 'qed';

    case QCOW2 = 'qcow2';

    case VMDK = 'vmdk';

    case CLOOP = 'cloop';
}
