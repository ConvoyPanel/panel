<?php

namespace App\Exceptions\Http\Server;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ConfigModifiedException extends ConflictHttpException
{
    /**
     * Thrown when Proxmox rejects a config update because the digest no longer
     * matches — i.e. the configuration changed between our read and write.
     *
     * Intentionally carries only a generic message and no previous exception so
     * the raw Proxmox response (which may reference node internals) never
     * reaches the client.
     */
    public function __construct()
    {
        parent::__construct(
            'The server configuration was modified since it was last loaded. Please reload and try again.',
        );
    }
}
