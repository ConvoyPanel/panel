<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Admin\Nodes\Settings\UpdateNodeRequest;
use Convoy\Http\Requests\Admin\Nodes\StoreNodeRequest;
use Convoy\Models\Node;
use Convoy\Transformers\Admin\NodeTransformer as AdminNodeTransformer;
use Convoy\Transformers\Application\NodeTransformer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class NodeController extends ApplicationApiController
{
    public function index(Request $request)
    {
        return Inertia::render('admin/nodes/Index', ['nodes' => fractal(Node::paginate($request->query('per_page') ?? 50), new AdminNodeTransformer)]);
    }

    public function create()
    {
        return Inertia::render('admin/nodes/Create');
    }

    public function show(Node $node)
    {
        return Inertia::render('admin/nodes/Show', [
            'node' => $node
        ]);
    }

    public function store(StoreNodeRequest $request)
    {
        $node = Node::create($request->validated());

        return redirect()->route('admin.nodes.show', [$node->id]);
    }

    public function search(Request $request)
    {
        $nodes = QueryBuilder::for(Node::query())
            ->allowedFilters(['name', 'cluster', 'hostname', 'port'])
            ->allowedSorts(['id'])
            ->paginate($request->query('per_page') ?? 50);

        return fractal($nodes, new NodeTransformer())->respond();
    }

    public function update(Node $node, UpdateNodeRequest $request)
    {
        $payload = $request->safe()->except(['token_id', 'secret']);

        if (isset($request->token_id))
            $payload['token_id'] = $request->safe()->only(['token_id']);

        if (isset($request->secret))
            $payload['secret'] = $request->safe()->only(['secret']);

        $node->update($payload);

        return back();
    }

    public function destroy(Node $node)
    {
        $node->delete();

        return back();
    }
}