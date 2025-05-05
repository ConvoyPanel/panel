<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Models\Node;
use Illuminate\Http\Request;
use App\Transformers\Admin\NetworkInterfaceTransformer;
use Illuminate\Http\JsonResponse;

use function fractal;

class NetworkInterfaceController
{
    public function index(Node $node): JsonResponse
    {
        return fractal($node->networkInterfaces, new NetworkInterfaceTransformer)->respond();
    }

    public function store(Request $request): JsonResponse
    {
        // TODO: finish store method
        return response()->noContent();
    }

    public function update(Request $request, Node $node): JsonResponse
    {
        // TODO: finish update method
        return response()->noContent();
    }

    public function destroy(Node $node): JsonResponse
    {
        // TODO: finish destroy method
        return response()->noContent();
    }
}
