<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Http\Requests\Admin\Nodes\TestNodeConnectionRequest;
use App\Models\Node;
use App\Services\Nodes\NodeConnectionTestService;

class NodeConnectionTestController
{
    public function __construct(private NodeConnectionTestService $service) {}

    public function __invoke(TestNodeConnectionRequest $request, ?Node $node = null)
    {
        $node = $node?->replicate() ?? new Node;
        $attributes = $request->validated();

        // Saved-node forms deliberately leave credentials blank to mean
        // "keep the existing value". Test an unsaved copy with the edited
        // connection fields while retaining those stored credentials.
        foreach (['token_id', 'token_secret'] as $credential) {
            if (! filled($attributes[$credential] ?? null)) {
                unset($attributes[$credential]);
            }
        }

        $node->fill($attributes);

        return $this->service->handle($node);
    }
}
