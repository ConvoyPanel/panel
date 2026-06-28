<?php

namespace App\Http\Controllers\Admin;

use App\Data\Coterm\CotermData;
use App\Data\Node\NodeData;
use App\Data\PaginationMeta;
use App\Http\Requests\Admin\Coterms\DeleteCotermRequest;
use App\Http\Requests\Admin\Coterms\StoreCotermRequest;
use App\Http\Requests\Admin\Coterms\UpdateAttachedNodesRequest;
use App\Http\Requests\Admin\Coterms\UpdateCotermRequest;
use App\Models\Coterm;
use App\Models\Filters\FiltersCotermWildcard;
use App\Models\Filters\FiltersNodeWildcard;
use App\Models\Node;
use App\Services\Coterm\CotermTokenCreationService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CotermController
{
    public function __construct(private CotermTokenCreationService $cotermTokenCreator) {}

    public function index(Request $request)
    {
        $coterms = QueryBuilder::for(Coterm::query())
            ->withCount(['nodes'])
            ->defaultSort('-id')
            ->allowedFilters(
                ['name', AllowedFilter::custom(
                    '*',
                    new FiltersCotermWildcard,
                )],
            )
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return PaginationMeta::paginate($coterms, CotermData::class);
    }

    public function show(Coterm $coterm)
    {
        $coterm->loadCount(['nodes']);

        return CotermData::from($coterm);
    }

    public function store(StoreCotermRequest $request)
    {
        $creds = $this->cotermTokenCreator->handle();
        $coterm = Coterm::create([
            ...$request->safe()->except('node_ids'),
            ...$creds,
        ]);
        if ($request->node_ids !== null) {
            Node::whereIn('id', $request->node_ids)->whereNull('coterm_id')->update(
                ['coterm_id' => $coterm->id],
            );
        }
        $coterm->loadCount(['nodes']);

        return CotermData::fromModel($coterm, includeToken: true);
    }

    public function update(UpdateCotermRequest $request, Coterm $coterm)
    {
        $coterm->update($request->validated());
        if ($request->node_ids !== null) {
            Node::whereIn('id', $request->node_ids)->whereNull('coterm_id')->update(
                ['coterm_id' => $coterm->id],
            );
            Node::where('coterm_id', $coterm->id)->whereNotIn('id', $request->node_ids)->update(
                ['coterm_id' => null],
            );
        }
        $coterm->loadCount(['nodes']);

        return CotermData::from($coterm);
    }

    public function getAttachedNodes(Request $request, Coterm $coterm)
    {
        $nodes = QueryBuilder::for($coterm->nodes())
            ->withCount('servers')
            ->allowedFilters(
                ['name', 'fqdn', AllowedFilter::exact(
                    'location_id',
                ), AllowedFilter::custom('*', new FiltersNodeWildcard)],
            )
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return PaginationMeta::paginate($nodes, NodeData::class);
    }

    public function updateAttachedNodes(UpdateAttachedNodesRequest $request, Coterm $coterm)
    {
        Node::whereIn('id', $request->node_ids)->whereNull('coterm_id')->update(
            ['coterm_id' => $coterm->id],
        );
        Node::where('coterm_id', $coterm->id)->whereNotIn('id', $request->node_ids)->update(
            ['coterm_id' => null],
        );
        $coterm->loadCount(['nodes']);

        return CotermData::from($coterm);
    }

    public function resetCotermToken(Coterm $coterm)
    {
        $creds = $this->cotermTokenCreator->handle();
        $coterm->update([
            'token_id' => $creds['token_id'],
            'token' => $creds['token'],
        ]);

        return CotermData::fromModel($coterm, includeToken: true);
    }

    public function destroy(DeleteCotermRequest $request, Coterm $coterm)
    {
        $coterm->delete();

        return response()->noContent();
    }
}
