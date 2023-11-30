<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Admin\Coterms\StoreCotermRequest;
use Convoy\Http\Requests\Admin\Coterms\UpdateAttachedNodesRequest;
use Convoy\Http\Requests\Admin\Coterms\UpdateCotermRequest;
use Convoy\Models\Coterm;
use Convoy\Models\Filters\FiltersCoterm;
use Convoy\Models\Filters\FiltersNode;
use Convoy\Services\Coterm\CotermTokenCreationService;
use Convoy\Transformers\Admin\CotermTransformer;
use Convoy\Transformers\Admin\NodeTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CotermController extends ApplicationApiController
{
    public function __construct(private CotermTokenCreationService $cotermTokenCreator)
    {
    }

    public function index(Request $request)
    {
        $addressPools = QueryBuilder::for(Coterm::query())
                                    ->withCount(['nodes'])
                                    ->defaultSort('-id')
                                    ->allowedFilters(
                                        ['name', AllowedFilter::custom(
                                            '*', new FiltersCoterm(),
                                        )],
                                    )
                                    ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return fractal($addressPools, new CotermTransformer())->respond();
    }

    public function show(Coterm $coterm)
    {
        $coterm->loadCount(['nodes']);

        return fractal($coterm, new CotermTransformer())->respond();
    }

    public function store(StoreCotermRequest $request)
    {
        $coterm = Coterm::create($request->safe()->except('node_ids'));
        if ($request->node_ids !== null) {
            $coterm->nodes()->attach($request->node_ids);
        }
        $coterm->loadCount(['nodes']);

        return fractal($coterm, new CotermTransformer())->respond();
    }

    public function update(UpdateCotermRequest $request, Coterm $coterm)
    {
        $coterm->update($request->validated());
        if ($request->node_ids !== null) {
            $coterm->nodes()->sync($request->node_ids);
        }
        $coterm->loadCount(['nodes']);

        return fractal($coterm, new CotermTransformer())->respond();
    }

    public function getAttachedNodes(Request $request, Coterm $coterm)
    {
        $nodes = QueryBuilder::for($coterm->nodes())
                             ->withCount('servers')
                             ->allowedFilters(
                                 ['name', 'fqdn', AllowedFilter::exact(
                                     'location_id',
                                 ), AllowedFilter::custom('*', new FiltersNode())],
                             )
                             ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return fractal($nodes, new NodeTransformer())->respond();
    }

    public function updateAttachedNodes(UpdateAttachedNodesRequest $request, Coterm $coterm)
    {
        $coterm->nodes()->sync($request->node_ids);
        $coterm->loadCount(['nodes']);

        return fractal($coterm, new CotermTransformer())->respond();
    }

    public function resetCotermToken(Coterm $coterm)
    {
        $creds = $this->cotermTokenCreator->handle();
        $coterm->update([
            'token_id' => $creds['token_id'],
            'token' => $creds['token'],
        ]);

        return fractal($coterm, new CotermTransformer())->parseIncludes('token')->respond();
    }

    public function destroy(Coterm $coterm)
    {
        return $this->returnNoContent();
    }
}
