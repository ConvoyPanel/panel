<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Node\VlanData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Nodes\NetworkInterfaces\VlanRequest;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Vlan;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;

class VlanController
{
    public function index(Node $node, NetworkInterface $networkInterface)
    {
        $usage = $networkInterface->vlanUsage();

        $vlans = $networkInterface->vlans()
            ->orderBy('tag')
            ->get()
            ->each(function (Vlan $vlan) use ($usage) {
                $vlan->servers_count = (int) $usage->get($vlan->tag, 0);
            });

        return VlanData::collect($vlans, DataCollection::class);
    }

    public function store(VlanRequest $request, Node $node, NetworkInterface $networkInterface)
    {
        $vlan = $networkInterface->vlans()->create($request->validated());

        // Declaring a VLAN doesn't move any server onto it, but the tag may
        // already be in use — a server could have been carrying it before
        // anyone wrote it down.
        $vlan->servers_count = (int) $networkInterface->vlanUsage()->get($vlan->tag, 0);

        Audit::record(
            AuditEvent::ADMIN_NODE_VLAN_CREATED,
            subject: $node,
            properties: ['interface' => $networkInterface->name, 'tag' => $vlan->tag],
        );

        return VlanData::from($vlan);
    }

    public function update(VlanRequest $request, Node $node, NetworkInterface $networkInterface, Vlan $vlan)
    {
        $vlan->update($request->validated());

        Audit::record(
            AuditEvent::ADMIN_NODE_VLAN_UPDATED,
            subject: $node,
            properties: ['interface' => $networkInterface->name, 'tag' => $vlan->tag],
        );

        $vlan->servers_count = (int) $networkInterface->vlanUsage()->get($vlan->tag, 0);

        return VlanData::from($vlan);
    }

    /**
     * Deleting a declaration does not detach anything. The tag a server gets is
     * still resolved from its own column, so a server on this tag keeps it and
     * the VLAN reappears in the tree as undeclared — no Proxmox sync needed.
     */
    public function destroy(Node $node, NetworkInterface $networkInterface, Vlan $vlan): Response
    {
        $tag = $vlan->tag;

        $vlan->delete();

        Audit::record(
            AuditEvent::ADMIN_NODE_VLAN_DELETED,
            subject: $node,
            properties: ['interface' => $networkInterface->name, 'tag' => $tag],
        );

        return response()->noContent();
    }
}
