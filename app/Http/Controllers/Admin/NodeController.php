<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\Node;
use Convoy\Transformers\Admin\NodeTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class NodeController extends ApplicationApiController
{
    public function index(Request $request)
    {
        $nodes = QueryBuilder::for(Node::query())
            ->withCount(['servers'])
            ->allowedFilters(['name', 'hostname'])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($nodes, new NodeTransformer())->respond();
    }

    public function destroy(Node $node)
    {
        $node->delete();

        return $this->returnNoContent();
    }
}
