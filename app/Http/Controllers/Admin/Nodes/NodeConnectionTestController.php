<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Node\Testing\ConnectionResultData;
use App\Http\Requests\Admin\Nodes\TestNodeConnectionRequest;
use App\Models\Node;
use App\Services\Nodes\NodeConnectionTestService;

class NodeConnectionTestController
{
    public function __construct(private NodeConnectionTestService $service) {}

    public function __invoke(TestNodeConnectionRequest $request)
    {
        $node = new Node;
        $node->fill($request->validated());

        return ConnectionResultData::from($this->service->handle($node));
    }
}
