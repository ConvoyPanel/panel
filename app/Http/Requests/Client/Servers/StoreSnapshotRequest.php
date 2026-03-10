<?php

namespace App\Http\Requests\Client\Servers;

use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use App\Models\Snapshot;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSnapshotRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        $server = $this->parameter('server', Server::class);

        return $this->user()->can('create', [Snapshot::class, $server]);
    }

    public function rules(): array
    {
        $rules = Snapshot::getRules();
        $server = $this->parameter('server', Server::class);

        return [
            'name' => [
                'required',
                'string',
                'max:40',
                Rule::unique('snapshots')
                    ->where('server_id', $server->id),
            ],
            'description' => $rules['description'],
            'includes_ram' => 'required|boolean',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $server = $this->parameter('server', Server::class);

                /** @var \App\Repositories\Proxmox\Server\ProxmoxSnapshotRepository $proxmoxRepo */
                $proxmoxRepo = app(\App\Repositories\Proxmox\Server\ProxmoxSnapshotRepository::class);
                $proxmoxSnapshots = $proxmoxRepo->setServer($server)->getSnapshots();

                $current = $proxmoxSnapshots->firstWhere('name', 'current');
                $parentName = $current ? $current->parentName : null;

                if ($parentName) {
                    if ($server->snapshots()->where('name', $parentName)->doesntExist()) {
                        $validator->errors()->add('base', 'The current snapshot state is out of sync with the database.');
                    }
                } elseif ($server->snapshots()->exists()) {
                    $validator->errors()->add('base', 'Cannot create a root snapshot when other snapshots already exist.');
                }
            },
        ];
    }
}
