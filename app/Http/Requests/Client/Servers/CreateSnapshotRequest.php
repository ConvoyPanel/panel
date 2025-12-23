<?php

namespace App\Http\Requests\Client\Servers;

use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use App\Models\Snapshot;

class CreateSnapshotRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        $server = $this->parameter('server', Server::class);

        return $this->user()->can('create', [Snapshot::class, $server]);
    }

    public function rules(): array
    {
        $rules = Snapshot::getRules();

        return [
            'name' => $rules['name'],
            'description' => $rules['description'],
            'includes_ram' => 'required|boolean',
        ];
    }
}
