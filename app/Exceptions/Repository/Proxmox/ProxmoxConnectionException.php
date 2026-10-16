<?php

namespace Convoy\Exceptions\Repository\Proxmox;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\RequestException;
use Convoy\Exceptions\Repository\RepositoryException;

class ProxmoxConnectionException extends RepositoryException
{
    /**
     * The message is the HTTP status and Proxmox's own error, e.g. "Proxmox
     * returned 500: Configuration file '…/109.conf' does not exist".
     *
     * It used to be the RequestException's message plus its full stack trace,
     * with the RequestException chained as the previous exception -- so a logged
     * error printed the trace three times: in the message, for this exception,
     * and for the previous one, both thrown from the same line. The response is
     * kept on the exception instead, for callers that need Proxmox's answer.
     */
    public function __construct(public readonly Response $response, RequestException $exception)
    {
        $error = trim((string) $response->json('message'))
            ?: trim($response->reason())
            ?: $exception->getMessage();

        parent::__construct("Proxmox returned {$response->status()}: {$error}", $exception->getCode());
    }
}
