<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\Nodes\StoreNodeRequest;
use App\Http\Requests\Admin\Nodes\UpdateNodeRequest;
use App\Models\Filters\FiltersNodeWildcard;
use App\Models\Node;
use App\Transformers\Admin\NodeTransformer;
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
                             ->allowedFilters(
                                 [AllowedFilter::exact('id'), 'name', 'fqdn', AllowedFilter::exact(
                                     'location_id',
                                 ), AllowedFilter::exact(
                                     'coterm_id',
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
