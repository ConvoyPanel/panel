<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Node\NetworkInterfaceData;
use App\Http\Requests\Admin\Nodes\DeleteNetworkInterfaceRequest;
use App\Http\Requests\Admin\Nodes\NetworkInterfaces\NetworkInterfaceRequest;
use App\Jobs\Server\SyncNetworkSettingsJob;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;

class NetworkInterfaceController
{
    public function index(Node $node)
    {
        return NetworkInterfaceData::collect(
            $node->networkInterfaces,
            DataCollection::class,
        );
    }

    public function store(NetworkInterfaceRequest $request, Node $node)
    {
        $data = $request->validated();
        if (($data['is_vlan_aware'] ?? false) === false) {
            $data['vlan_tag'] = null;
        }

        $interface = $node->networkInterfaces()->create($data);

        return NetworkInterfaceData::from($interface);
    }

    public function update(NetworkInterfaceRequest $request, Node $node, NetworkInterface $networkInterface)
    {
        $data = $request->validated();
        if (($data['is_vlan_aware'] ?? $networkInterface->is_vlan_aware) === false) {
            $data['vlan_tag'] = null;
        }

        $networkInterface->update($data);

        if ($networkInterface->wasChanged(['name', 'is_vlan_aware', 'vlan_tag'])) {
            if (! $networkInterface->is_vlan_aware) {
                Server::query()
                    ->where('network_interface_id', $networkInterface->id)
                    ->update(['vlan_tag' => null]);
            }

            Server::query()
                ->where('network_interface_id', $networkInterface->id)
                ->each(fn (Server $server) => dispatch(new SyncNetworkSettingsJob($server)));
        }

        return NetworkInterfaceData::from($networkInterface);
    }

    public function destroy(DeleteNetworkInterfaceRequest $request, Node $node, NetworkInterface $networkInterface): Response
    {
        $networkInterface->delete();

        return response()->noContent();
    }
}
