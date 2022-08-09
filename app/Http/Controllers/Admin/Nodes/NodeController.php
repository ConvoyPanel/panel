<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Requests\Admin\Nodes\Settings\UpdateNodeRequest;
use App\Http\Requests\Admin\Nodes\StoreNodeRequest;
use App\Models\Node;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NodeController extends ApplicationApiController
{
    public function index()
    {
        return Inertia::render('admin/nodes/Index', ['nodes' => Node::all()]);
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
        return Node::search($request->search)->get();
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