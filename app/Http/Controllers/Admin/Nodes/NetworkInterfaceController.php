<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Node\NetworkInterfaceData;
use App\Http\Requests\Admin\Nodes\DeleteNetworkInterfaceRequest;
use App\Http\Requests\Admin\Nodes\NetworkInterfaces\NetworkInterfaceRequest;
use App\Models\NetworkInterface;
use App\Models\Node;
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
        $interface = $node->networkInterfaces()->create($request->validated());

        return NetworkInterfaceData::from($interface);
    }

    public function update(NetworkInterfaceRequest $request, Node $node, NetworkInterface $networkInterface)
    {
        $networkInterface->update($request->validated());

        return NetworkInterfaceData::from($networkInterface);
    }

    public function destroy(DeleteNetworkInterfaceRequest $request, Node $node, NetworkInterface $networkInterface): Response
    {
        $networkInterface->delete();

        return response()->noContent();
    }
}
