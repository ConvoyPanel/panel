<?php

namespace App\Exceptions\Service\Server\Allocation;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Proxmox accepted the resize and then its task failed.
 *
 * 503 rather than 500: every failure that reaches here is a node-side condition
 * that a later attempt can clear (storage still flushing a freshly imported
 * disk, a lock held by another task), not a bad request the caller can fix.
 */
class DiskResizeFailedException extends ServiceUnavailableHttpException implements HasErrorCode
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct(null, "Proxmox could not resize the disk: {$reason}");
    }

    public function errorCode(): string
    {
        return 'disk_resize_failed';
    }
}
