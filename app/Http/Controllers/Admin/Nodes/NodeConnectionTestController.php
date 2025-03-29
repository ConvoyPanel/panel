<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Http\Requests\Admin\Nodes\TestNodeConnectionRequest;
use App\Models\Node;
use App\Services\Nodes\NodeConnectionTestService;
use App\Transformers\Admin\NodeConnectionResultTransformer;

use function fractal;

class NodeConnectionTestController
{
    public function __construct(private NodeConnectionTestService $service) {}

    public function __invoke(TestNodeConnectionRequest $request)
    {
        $node = new Node;
        $node->fill($request->validated());

        return fractal(
            $this->service->handle($node),
            new NodeConnectionResultTransformer
        )->respond();
    }
}
