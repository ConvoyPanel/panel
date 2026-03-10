<?php

namespace App\Http\Requests\Client\Servers;

use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use App\Models\Snapshot;

class UpdateSnapshotRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        $server = $this->parameter('server', Server::class);
        $snapshot = $this->parameter('snapshot', Snapshot::class);

        return $this->user()->can('update', [$snapshot, $server]);
    }

    public function rules(): array
    {
        $rules = Snapshot::getRules();

        return [
            'description' => $rules['description'],
        ];
    }
}

