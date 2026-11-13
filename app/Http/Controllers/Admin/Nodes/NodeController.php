<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\ApiController;
use Convoy\Http\Requests\Admin\Nodes\StoreNodeRequest;
use Convoy\Http\Requests\Admin\Nodes\UpdateNodeRequest;
use Convoy\Models\Filters\FiltersExactId;
use Convoy\Models\Filters\FiltersNodeWildcard;
use Convoy\Models\Node;
use Convoy\Transformers\Admin\NodeTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class NodeController extends ApiController
{
    public function index(Request $request)
    {
        $nodes = QueryBuilder::for(Node::query())
                             ->withCount(['servers'])
                             // MySQL handed rows back in id order without being asked;
                             // Postgres doesn't, and pages would overlap.
                             ->defaultSort('id')
                             ->allowedFilters(
                                 [AllowedFilter::custom('id', new FiltersExactId()), 'name', 'fqdn', AllowedFilter::custom(
                                     'location_id', new FiltersExactId(),
                                 ), AllowedFilter::custom(
                                     'coterm_id', new FiltersExactId(),
                                 )->nullable(), AllowedFilter::custom(
                                     '*',
                                     new FiltersNodeWildcard(),
                                 )],
                             )
                             ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return fractal($nodes, new NodeTransformer())->respond();
    }

    public function show(Node $node)
    {
        $node->append(['memory_allocated', 'disk_allocated']);

        $node->loadCount('servers');

        return fractal($node, new NodeTransformer())->respond();
    }

    public function store(StoreNodeRequest $request)
    {
        $node = Node::create($request->validated());

        return fractal($node, new NodeTransformer())->respond();
    }

    public function update(UpdateNodeRequest $request, Node $node)
    {
        $node->update($request->validated());

        return fractal($node, new NodeTransformer())->respond();
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

        return $this->returnNoContent();
    }
}
