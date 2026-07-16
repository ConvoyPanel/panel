<?php

namespace App\Data\Server\Power;

use App\Enums\Server\PowerCommand;
use Spatie\LaravelData\Data;

/**
 * The power command currently in flight for a server, exposed on the server
 * state response so the UI can render "restart in progress…" and reject
 * duplicate/conflicting requests while it is held.
 */
class PendingPowerActionData extends Data
{
    public function __construct(
        public PowerCommand $command,
        public string $requestedAt,
    ) {}
}
