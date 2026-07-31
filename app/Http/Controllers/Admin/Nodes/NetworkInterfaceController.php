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
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class NetworkInterfaceController
{
    public function index(Node $node)
    {
        $interfaces = $node->networkInterfaces()
            ->withCount(['servers', 'addressBlockGroups'])
            ->with('vlans')
            ->get();

        return NetworkInterfaceData::collect(
            $this->withVlanUsage($interfaces),
            DataCollection::class,
        );
    }

    /**
     * Resolve VLAN usage for the whole list in one query. Without this each
     * interface would ask for its own counts while the data object is being
     * built — the list is short, but the query count would track it.
     *
     * @param  Collection<int, NetworkInterface>  $interfaces
     * @return Collection<int, NetworkInterface>
     */
    private function withVlanUsage(Collection $interfaces): Collection
    {
        $usage = NetworkInterface::vlanUsageFor($interfaces);

        return $interfaces->each(function (NetworkInterface $interface) use ($usage) {
            $interface->resolvedVlanUsage = $usage->get($interface->id) ?? collect();
        });
    }

    public function store(NetworkInterfaceRequest $request, Node $node)
    {
        $data = $request->validated();
        if (($data['is_vlan_aware'] ?? false) === false) {
            $data['vlan_tag'] = null;
        }

        $interface = $node->networkInterfaces()->create($data);

        return $this->respondWith($interface);
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

                // A VLAN on a bridge that no longer trunks is unreachable: the
                // sync forces a null tag on every server here, so nothing can
                // resolve to it. Drop the declarations alongside the server
                // tags this already clears, rather than leave a tree of VLANs
                // that can never have a member.
                $networkInterface->vlans()->delete();
            }

            Server::query()
                ->where('network_interface_id', $networkInterface->id)
                ->each(fn (Server $server) => dispatch(new SyncNetworkSettingsJob($server)));
        }

        return $this->respondWith($networkInterface);
    }

    /**
     * The client merges a write response straight into its cached list, so
     * every write path has to carry the same derived fields the list does —
     * otherwise editing an interface would blank the servers, pools and VLANs
     * already on it until the next refetch.
     */
    private function respondWith(NetworkInterface $interface): NetworkInterfaceData
    {
        $interface->resolvedVlanUsage = null;

        return NetworkInterfaceData::from(
            $interface
                ->loadCount(['servers', 'addressBlockGroups'])
                ->load('vlans'),
        );
    }

    public function destroy(DeleteNetworkInterfaceRequest $request, Node $node, NetworkInterface $networkInterface): Response
    {
        $networkInterface->delete();

        return response()->noContent();
    }
}
