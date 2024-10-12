<?php

namespace App\Http\Requests\Client\Servers\Backups;

use App\Http\Requests\BaseApiRequest;
use App\Models\Server;

class DeleteBackupRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        $server = $this->parameter('server', Server::class);

        return $this->user()->can('delete', $server);
    }
}
