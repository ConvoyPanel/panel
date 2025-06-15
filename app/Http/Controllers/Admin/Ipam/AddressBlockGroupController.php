<?php

namespace App\Http\Controllers\Admin\Ipam;

use App\Http\Requests\Admin\AddressBlockGroups\AddressBlockGroupRequest;
use App\Models\AddressBlockGroup;
use App\Models\Filters\FiltersAddressBlockGroupWildcard;
use App\Models\Filters\FiltersNodeWildcard;
use App\Models\Filters\FiltersServerWildcard;
use App\Models\Server;
use App\Transformers\Admin\AddressBlockGroupTransformer;
use App\Transformers\Admin\NodeTransformer;
use App\Transformers\Client\ServerTransformer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

use function fractal;
use function min;

class AddressBlockGroupController
{
    public function __construct() {}

    public function index(Request $request): JsonResponse
    {
        $groups = QueryBuilder::for(AddressBlockGroup::query())
            ->withCount('addressBlocks', 'nodes')
            ->defaultSort('-id')
            ->allowedFilters(
                AllowedFilter::custom(
                    '*', new FiltersAddressBlockGroupWildcard,
                ),
                'name',
                'description',
            )
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return fractal($groups, new AddressBlockGroupTransformer)->respond();
    }

    public function show(AddressBlockGroup $addressBlockGroup): JsonResponse
    {
        $addressBlockGroup->loadCount('addressBlocks', 'nodes');

        return fractal($addressBlockGroup, new AddressBlockGroupTransformer)->respond();
    }

    public function store(AddressBlockGroupRequest $request): JsonResponse
    {
        $addressBlockGroup = AddressBlockGroup::create($request->validated());

        return fractal($addressBlockGroup, new AddressBlockGroupTransformer)->respond();
    }

    public function update(AddressBlockGroupRequest $request, AddressBlockGroup $addressBlockGroup): JsonResponse
    {
        $addressBlockGroup->update($request->validated());

        return fractal($addressBlockGroup, new AddressBlockGroupTransformer)->respond();
    }

    public function destroy(AddressBlockGroup $addressBlockGroup): Response
    {
        Gate::authorize('delete', $addressBlockGroup);

        $addressBlockGroup->delete();

        return response()->noContent();
    }

    public function getAttachedNodes(Request $request, AddressBlockGroup $addressBlockGroup): JsonResponse
    {
        $nodes = QueryBuilder::for($addressBlockGroup->nodes())
            ->withCount('servers')
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::custom('*', new FiltersNodeWildcard),
                AllowedFilter::exact('id'),
            ])
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return fractal($nodes, new NodeTransformer)->respond();
    }

    public function getCompatibleServers(Request $request, AddressBlockGroup $addressBlockGroup): JsonResponse
    {
        $servers = QueryBuilder::for(Server::query())
            ->with(['node' => function (BelongsTo $query): void {
                $query->withCount('servers');
            }])
            ->whereHas('node.networkInterfaces.addressBlockGroups', function (Builder $query) use ($addressBlockGroup): void {
                $query->where('address_block_groups.id', $addressBlockGroup->id);
            })
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::custom('*', new FiltersServerWildcard),
                AllowedFilter::exact('node_id'),
                AllowedFilter::exact('user_id'),
                'name',
            ])
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return fractal($servers, new ServerTransformer)->parseIncludes($request->include)->respond();
    }
}
