<?php

namespace Convoy\Http\Requests\Client\Servers\Backups;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Server;

class RestoreBackupRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        $server = $this->parameter('server', Server::class);

        return $this->user()->can('restore', $server);
    }
}
