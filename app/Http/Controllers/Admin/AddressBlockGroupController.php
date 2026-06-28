<?php

namespace App\Http\Controllers\Admin;

use App\Data\Ipam\AddressBlockGroupData;
use App\Data\Node\NetworkInterfaceData;
use App\Data\PaginationMeta;
use App\Data\Server\ServerData;
use App\Http\Requests\Admin\AddressBlockGroups\AddressBlockGroupRequest;
use App\Http\Requests\Admin\AddressBlockGroups\AttachNodeRequest;
use App\Http\Requests\Admin\AddressBlockGroups\DetachNodeRequest;
use App\Models\AddressBlockGroup;
use App\Models\Filters\FiltersAddressBlockGroupWildcard;
use App\Models\Filters\FiltersServerWildcard;
use App\Models\Node;
use App\Models\Server;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AddressBlockGroupController
{
    public function __construct() {}

    public function index(Request $request)
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

        return PaginationMeta::paginate($groups, AddressBlockGroupData::class);
    }

    public function show(AddressBlockGroup $addressBlockGroup)
    {
        $addressBlockGroup->loadCount('addressBlocks', 'nodes');

        return AddressBlockGroupData::from($addressBlockGroup);
    }

    public function store(AddressBlockGroupRequest $request)
    {
        $addressBlockGroup = AddressBlockGroup::create($request->validated());

        return AddressBlockGroupData::from($addressBlockGroup);
    }

    public function update(AddressBlockGroupRequest $request, AddressBlockGroup $addressBlockGroup)
    {
        $addressBlockGroup->update($request->validated());

        return AddressBlockGroupData::from($addressBlockGroup);
    }

    public function destroy(AddressBlockGroup $addressBlockGroup): Response
    {
        Gate::authorize('delete', $addressBlockGroup);

        $addressBlockGroup->delete();

        return response()->noContent();
    }

    public function getAttachedNodes(Request $request, AddressBlockGroup $addressBlockGroup)
    {
        $interfaces = QueryBuilder::for($addressBlockGroup->networkInterfaces())
            ->with(['node' => function ($query) {
                $query->withCount('servers');
            }])
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::callback('*', function (Builder $query, $value) {
                    $query->where('name', 'LIKE', "%$value%")
                        ->orWhereHas('node', function (Builder $query) use ($value) {
                            $query->where('fqdn', 'LIKE', "%$value%")
                                ->orWhere('display_name', 'LIKE', "%$value%");
                        });
                }),
                AllowedFilter::exact('node_id'),
            ])
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($interfaces, NetworkInterfaceData::class);
    }

    public function attachNode(AttachNodeRequest $request, AddressBlockGroup $addressBlockGroup)
    {
        $addressBlockGroup->networkInterfaces()->syncWithoutDetaching([
            $request->input('network_interface_id')
        ]);

        return response()->json([], 201);
    }

    public function detachNode(DetachNodeRequest $request, AddressBlockGroup $addressBlockGroup, Node $node): Response
    {
        $interfaceIds = $node->networkInterfaces()->pluck('id');
        $addressBlockGroup->networkInterfaces()->detach($interfaceIds);

        return response()->noContent();
    }

    public function getCompatibleServers(Request $request, AddressBlockGroup $addressBlockGroup)
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

        return PaginationMeta::paginate($servers, ServerData::class);
    }
}
