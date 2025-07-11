<?php

namespace App\Http\Controllers\Admin\Nodes;

use Illuminate\Http\Response;
use App\Models\NetworkInterface;
use App\Http\Requests\Admin\Nodes\NetworkInterfaces\NetworkInterfaceRequest;
use App\Models\Node;
use App\Transformers\Admin\NetworkInterfaceTransformer;
use Illuminate\Http\JsonResponse;

use function fractal;

class NetworkInterfaceController
{
    public function index(Node $node): JsonResponse
    {
        return fractal($node->networkInterfaces, new NetworkInterfaceTransformer)->respond();
    }

    public function store(NetworkInterfaceRequest $request, Node $node): JsonResponse
    {
        $interface = $node->networkInterfaces()->create($request->validated());

        return fractal($interface, new NetworkInterfaceTransformer)->respond();
    }

    public function update(NetworkInterfaceRequest $request, Node $node, NetworkInterface $networkInterface): JsonResponse
    {
        $networkInterface->update($request->validated());

        return fractal($networkInterface, new NetworkInterfaceTransformer)->respond();
    }

    public function destroy(Node $node, NetworkInterface $networkInterface): Response
    {
        // TODO: finish destroy method
        return response()->noContent();
    }
}
