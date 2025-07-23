<?php

namespace App\Exceptions\Repository\Proxmox;

use Throwable;
use App\Exceptions\DisplayException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class NextVMIDRetrievalException extends DisplayException {
    public function __construct(?Throwable $previous = null) {
        parent::__construct(
            message: 'Unable to retrieve the next VMID from Proxmox.',
            previous:  $previous,
            statusCode: Response::HTTP_SERVICE_UNAVAILABLE
        );
    }
}