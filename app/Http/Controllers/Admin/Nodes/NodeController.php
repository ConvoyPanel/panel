<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Admin\Nodes\Settings\UpdateCotermRequest;
use Convoy\Http\Requests\Admin\Nodes\StoreNodeRequest;
use Convoy\Http\Requests\Admin\Nodes\UpdateNodeRequest;
use Convoy\Models\Filters\FiltersNode;
use Convoy\Models\Node;
use Convoy\Services\Coterm\CotermTokenCreationService;
use Convoy\Transformers\Admin\NodeTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class NodeController extends ApplicationApiController
{
    public function __construct(private CotermTokenCreationService $cotermTokenCreator)
    {
    }

    public function index(Request $request)
    {
        $nodes = QueryBuilder::for(Node::query())
            ->withCount(['servers'])
            ->allowedFilters(['name', 'fqdn', AllowedFilter::exact('location_id'), AllowedFilter::custom('*', new FiltersNode)])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

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

        return fractal($node, new NodeTransformer)->respond();
    }

    public function update(UpdateNodeRequest $request, Node $node)
    {
        $node->update($request->validated());

        return fractal($node, new NodeTransformer)->respond();
    }

    public function updateCoterm(UpdateCotermRequest $request, Node $node)
    {
        $payload = $request->validated();

        if ($payload['coterm_enabled'] && (empty($node->coterm_token_id) || empty($node->coterm_token))) {
            $creds = $this->cotermTokenCreator->handle();
            $payload['coterm_token_id'] = $creds['token_id'];
            $payload['coterm_token'] = $creds['token'];
        }



        

        $node->update($payload);

        return new JsonResponse([
            'data' => [
                'is_enabled' => $node->coterm_enabled,
                'is_tls_enabled' => $node->coterm_tls_enabled,
                'fqdn' => $node->coterm_fqdn,
                'port' => $node->coterm_port,
                'token' => isset($creds) ? "{$node->coterm_token_id}|{$node->coterm_token}" : null,
            ]
        ]);
    }

    public function resetCotermToken(Node $node)
    {
        if (!$node->coterm_enabled) {
            throw new AccessDeniedHttpException('Coterm isn\'t enabled on this node.');
        }

        $creds = $this->cotermTokenCreator->handle();

        $node->update([
            'coterm_token_id' => $creds['token_id'],
            'coterm_token' => $creds['token'],
        ]);

        return new JsonResponse([
            'data' => [
                'token' => "{$node->coterm_token_id}|{$node->coterm_token}",
            ]
        ]);
    }

    public function destroy(Node $node)
    {
        $node->loadCount('servers');

        if ($node->servers_count > 0) {
            throw new AccessDeniedHttpException('This node cannot be deleted with servers still associated.');
        }

        $node->delete();

        return $this->returnNoContent();
    }
}
