<?php

namespace App\Enums\Server\Disk;

/**
 * Defines the Asynchronous I/O (AIO) mode for a virtual disk.
 */
enum DiskAioMode: string
{
    case NATIVE = 'native';

    case THREADS = 'threads';

    case IO_URING = 'io_uring';
}
