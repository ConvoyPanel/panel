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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $server = $this->parameter('server', Server::class);

            /** @var \App\Repositories\Proxmox\Server\ProxmoxSnapshotRepository $proxmoxRepo */
            $proxmoxRepo = app(\App\Repositories\Proxmox\Server\ProxmoxSnapshotRepository::class);
            $proxmoxSnapshots = $proxmoxRepo->setServer($server)->getSnapshots();

            $current = $proxmoxSnapshots->firstWhere('name', 'current');
            $parentName = $current ? $current->parentName : null;

            if (is_null($parentName) && $server->snapshots()->exists()) {
                $validator->errors()->add('base', 'Cannot create a root snapshot when other snapshots already exist.');
            }
        });
    }
}
