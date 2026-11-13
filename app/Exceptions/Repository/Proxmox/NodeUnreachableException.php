<?php

namespace Convoy\Exceptions\Repository\Proxmox;

use Convoy\Exceptions\Repository\RepositoryException;
use Convoy\Models\Node;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The panel couldn't reach a node at all: the connection was refused, timed
 * out, or failed TLS before Proxmox could answer. (When Proxmox does answer
 * with an error, that's a {@see ProxmoxConnectionException}.)
 *
 * Laravel turns these into a bare ConnectionException, which a production
 * panel showed as "Server Error". This one renders its message instead. The
 * message stays generic because clients see it too: the node's address and
 * the cURL error go to the log as context, not to the browser. Nothing is
 * chained as the previous exception, so a logged error carries one stack
 * trace, not two.
 */
class NodeUnreachableException extends RepositoryException implements HttpExceptionInterface
{
    public function __construct(public readonly Node $node, private readonly string $reason)
    {
        parent::__construct(
            "Couldn't connect to the node. It may be offline or unreachable from the panel; try again once it's back."
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_GATEWAY;
    }

    public function getHeaders(): array
    {
        return [];
    }

    /**
     * Logged with the exception, never shown to the user.
     */
    public function context(): array
    {
        return [
            'node' => $this->node->id,
            'reason' => $this->reason,
        ];
    }
}
