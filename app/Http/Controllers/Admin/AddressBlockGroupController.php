<?php

namespace App\Http\Controllers\Admin;

use App\Data\Ipam\AddressBlockGroupData;
use App\Data\Ipam\AddressCapacityData;
use App\Data\Ipam\IpamSummaryData;
use App\Data\Ipam\NearlyFullBlockData;
use App\Data\Node\NetworkInterfaceData;
use App\Data\PaginationMeta;
use App\Data\Server\ServerData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\AddressBlockGroups\AddressBlockGroupRequest;
use App\Http\Requests\Admin\AddressBlockGroups\AttachNodeRequest;
use App\Http\Requests\Admin\AddressBlockGroups\DetachNodeRequest;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Filters\FiltersAddressBlockGroupWildcard;
use App\Models\Filters\FiltersServerWildcard;
use App\Models\NetworkInterface;
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
            ->withAddressStateCounts(denseOnly: true)
            // The pool's total size is the sum of its blocks' geometry, so the rows come along
            // rather than costing a query per pool to add them up.
            ->with('addressBlocks:id,address_block_group_id,base_ip,prefix_length_from,prefix_length_to')
            ->defaultSort('-id')
            ->allowedFilters(
                AllowedFilter::custom(
                    '*', new FiltersAddressBlockGroupWildcard,
                ),
                AllowedFilter::callback(
                    'node_id',
                    function (Builder $query, $value): void {
                        $nodeIds = is_array($value) ? $value : [$value];

                        $query->whereHas(
                            'networkInterfaces',
                            function (Builder $query) use ($nodeIds): void {
                                $query->whereIn('node_id', $nodeIds);
                            },
                        );
                    },
                ),
                'name',
                'description',
            )
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($groups, AddressBlockGroupData::class);
    }

    /**
     * The IPAM index's headline figures, across every pool.
     *
     * Deliberately not derived from the page of pools the table is showing: the moment there is a
     * second page, a total that quietly means "of the rows you can see" is wrong, and a wrong
     * headline is worse than none. One pass over the blocks (there are tens, not millions) carries
     * both the roll-up and the "which block is about to fill up" answer the tile needs.
     */
    public function summary()
    {
        $blocks = AddressBlock::query()->withAddressStateCounts()->get();

        $generated = $assigned = $reserved = $system = $available = 0;
        $totalUnits = 0;
        $denseBlocks = 0;
        $sparseBlocks = 0;
        $nearlyFull = [];

        foreach ($blocks as $block) {
            $capacity = AddressCapacityData::forBlock($block);

            // A sparse block contributes neither a denominator nor a numerator: counting its
            // minted addresses against the sized blocks' total would read past 100% full.
            if ($capacity->totalUnits === null) {
                $sparseBlocks++;

                continue;
            }

            $denseBlocks++;
            $generated += $capacity->generatedCount;
            $assigned += $capacity->assignedCount;
            $reserved += $capacity->reservedCount;
            $system += $capacity->systemCount;
            $available += $capacity->availableCount;

            $totalUnits += $capacity->totalUnits;

            // A block with nothing generated has no ratio, so it cannot be "nearly full" — it is
            // not set up yet, which is a different problem and a different message.
            $usable = $capacity->totalUnits - $capacity->systemCount;

            if ($capacity->generatedCount < 1 || $usable < 1) {
                continue;
            }

            $percent = (($capacity->assignedCount + $capacity->reservedCount) / $usable) * 100;

            if ($percent >= 90) {
                $nearlyFull[] = new NearlyFullBlockData(
                    id: $block->id,
                    addressBlockGroupId: $block->address_block_group_id,
                    label: $block->base_ip.'/'.$block->prefix_length_from,
                    percent: round($percent, 1),
                );
            }
        }

        usort($nearlyFull, fn (NearlyFullBlockData $a, NearlyFullBlockData $b) => $b->percent <=> $a->percent);

        return new IpamSummaryData(
            capacity: new AddressCapacityData(
                totalUnits: $denseBlocks > 0 ? $totalUnits : null,
                isSparse: $denseBlocks === 0 && $sparseBlocks > 0,
                generatedCount: $generated,
                assignedCount: $assigned,
                reservedCount: $reserved,
                systemCount: $system,
                availableCount: $available,
                sparseBlockCount: $sparseBlocks,
            ),
            poolsCount: AddressBlockGroup::query()->count(),
            blocksCount: $blocks->count(),
            nodesCount: NetworkInterface::query()
                ->whereHas('addressBlockGroups')
                ->distinct()
                ->count('node_id'),
            blocksNearlyFull: count($nearlyFull),
            fullestBlock: $nearlyFull[0] ?? null,
        );
    }

    public function show(AddressBlockGroup $addressBlockGroup)
    {
        $addressBlockGroup->loadCount('addressBlocks', 'nodes');
        $addressBlockGroup->loadCount(AddressBlockGroup::addressStateCounts(denseOnly: true));
        $addressBlockGroup->load('addressBlocks:id,address_block_group_id,base_ip,prefix_length_from,prefix_length_to');

        return AddressBlockGroupData::from($addressBlockGroup);
    }

    public function store(AddressBlockGroupRequest $request)
    {
        $addressBlockGroup = AddressBlockGroup::create($request->validated());

        Audit::record(
            AuditEvent::ADMIN_ADDRESS_BLOCK_GROUP_CREATED,
            subject: $addressBlockGroup,
            properties: ['name' => $addressBlockGroup->name],
        );

        return AddressBlockGroupData::from($addressBlockGroup);
    }

    public function update(AddressBlockGroupRequest $request, AddressBlockGroup $addressBlockGroup)
    {
        $addressBlockGroup->update($request->validated());

        Audit::record(
            AuditEvent::ADMIN_ADDRESS_BLOCK_GROUP_UPDATED,
            subject: $addressBlockGroup,
            properties: [
                'name' => $addressBlockGroup->name,
                'changed' => array_keys($addressBlockGroup->getChanges()),
            ],
        );

        return AddressBlockGroupData::from($addressBlockGroup);
    }

    public function destroy(AddressBlockGroup $addressBlockGroup): Response
    {
        Gate::authorize('delete', $addressBlockGroup);

        $name = $addressBlockGroup->name;

        $addressBlockGroup->delete();

        Audit::record(
            AuditEvent::ADMIN_ADDRESS_BLOCK_GROUP_DELETED,
            subject: $addressBlockGroup,
            properties: ['name' => $name],
        );

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
            $request->input('network_interface_id'),
        ]);

        Audit::record(
            AuditEvent::ADMIN_ADDRESS_BLOCK_GROUP_NODE_ATTACHED,
            subject: $addressBlockGroup,
            properties: ['network_interface_id' => (int) $request->input('network_interface_id')],
        );

        return response()->json([], 201);
    }

    public function detachNode(DetachNodeRequest $request, AddressBlockGroup $addressBlockGroup, Node $node): Response
    {
        $interfaceIds = $node->networkInterfaces()->pluck('id');
        $addressBlockGroup->networkInterfaces()->detach($interfaceIds);

        Audit::record(
            AuditEvent::ADMIN_ADDRESS_BLOCK_GROUP_NODE_DETACHED,
            subject: $addressBlockGroup,
            properties: ['node' => $node->name],
        );

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
