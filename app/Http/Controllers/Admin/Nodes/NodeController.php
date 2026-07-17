<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Node\NodeData;
use App\Data\PaginationMeta;
use App\Http\Requests\Admin\Nodes\StoreNodeRequest;
use App\Http\Requests\Admin\Nodes\UpdateNodeRequest;
use App\Models\Filters\FiltersNodeWildcard;
use App\Models\Node;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class NodeController
{
    public function index(Request $request)
    {
        $nodes = QueryBuilder::for(Node::query())
            ->withCount(['servers'])
            ->allowedFilters([
                AllowedFilter::custom('*', new FiltersNodeWildcard),
                AllowedFilter::exact('id'),
                'display_name',
                'fqdn',
                AllowedFilter::exact('location_id'),
                AllowedFilter::exact('anchor_id')->nullable(),
            ])
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return PaginationMeta::paginate($nodes, NodeData::class);
    }

    public function show(Node $node)
    {
        $node->append(['memory_allocated'])
            ->loadCount('servers');

        return NodeData::from($node);
    }

    public function store(StoreNodeRequest $request)
    {
        $node = Node::create($request->validated())
            ->append(['memory_allocated'])
            ->loadCount('servers');

        return NodeData::from($node);
    }

    public function update(UpdateNodeRequest $request, Node $node)
    {
        $node->update($request->validated());

        $node->append(['memory_allocated'])
            ->loadCount('servers');

        return NodeData::from($node);
    }

    public function destroy(Node $node)
    {
        $node->loadCount('servers');

        if ($node->servers_count > 0) {
            throw new AccessDeniedHttpException(
                'This node cannot be deleted with servers still associated.',
            );
        }

        $node->delete();

        return response()->noContent();
    }
}
