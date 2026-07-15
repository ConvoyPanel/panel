<?php

namespace App\Exceptions\Proxmox;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

class NextVMIDRetrievalException extends ServiceUnavailableHttpException implements HasErrorCode
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(null, 'Unable to retrieve the next VMID from Proxmox.', $previous);
    }

    public function errorCode(): string
    {
        return 'next_vmid_retrieval_failed';
    }
}
