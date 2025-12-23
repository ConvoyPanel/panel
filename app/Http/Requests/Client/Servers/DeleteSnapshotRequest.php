<?php

namespace App\Http\Requests\Client\Servers;

use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use App\Models\Snapshot;

class DeleteSnapshotRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        $snapshot = $this->parameter('snapshot', Snapshot::class);
        $server = $this->parameter('server', Server::class);

        return $this->user()->can('delete', $snapshot, $server);
    }
}
