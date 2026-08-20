<?php

namespace App\Http\Controllers\Admin;

use App\Data\Ipam\AddressBlockData;
use App\Data\PaginationMeta;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\AddressBlocks\StoreAddressBlockRequest;
use App\Http\Requests\Admin\AddressBlocks\UpdateAddressBlockRequest;
use App\Jobs\Server\BatchSyncNetworkSettingsJob;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Filters\FiltersAddressBlockWildcard;
use Gate;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class AddressBlockController
{
    public function __construct(private ConnectionInterface $connection) {}

    public function index(Request $request, AddressBlockGroup $addressBlockGroup)
    {
        $blocks = QueryBuilder::for($addressBlockGroup->addressBlocks())
            ->defaultSort('-id')
            ->allowedFilters(
                AllowedFilter::custom('*', new FiltersAddressBlockWildcard),
                'name',
                'description',
                AllowedFilter::exact('version'),
                AllowedFilter::exact('base_ip'),
                AllowedFilter::exact('gateway'),
                AllowedFilter::exact('mac_address'),
                AllowedFilter::exact('prefix_length_to'),
                AllowedFilter::exact('prefix_length_from'),
            )
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($blocks, AddressBlockData::class);
    }

    public function show(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock)
    {
        return AddressBlockData::from($addressBlock);
    }

    public function store(StoreAddressBlockRequest $request, AddressBlockGroup $addressBlockGroup)
    {
        $block = $addressBlockGroup->addressBlocks()->create($request->validated());

        Audit::record(
            AuditEvent::ADMIN_ADDRESS_BLOCK_CREATED,
            subject: $block,
            properties: ['base_ip' => $block->base_ip, 'group' => $addressBlockGroup->name],
        );

        return AddressBlockData::from($block);
    }

    /**
     * @throws Throwable
     */
    public function update(
        UpdateAddressBlockRequest $request,
        AddressBlockGroup $addressBlockGroup,
        AddressBlock $addressBlock,
    ) {
        $this->connection->transaction(
            function () use ($request, $addressBlock) {
                if (
                    $addressBlock->base_ip !== $request->string('base_ip')->toString() ||
                    $addressBlock->prefix_length_from !== $request->integer('prefix_length_from') ||
                    $addressBlock->prefix_length_to !== $request->integer('prefix_length_to')
                ) {
                    $addressBlock->addresses()->delete();
                }

                $addressBlock->update($request->validated());

                if (
                    $addressBlock->mac_address !== $request->input('mac_address') ||
                    $addressBlock->gateway !== $request->input('gateway')
                ) {
                    dispatch(new BatchSyncNetworkSettingsJob($addressBlock));
                }
            },
        );

        Audit::record(
            AuditEvent::ADMIN_ADDRESS_BLOCK_UPDATED,
            subject: $addressBlock,
            properties: [
                'base_ip' => $addressBlock->base_ip,
                'changed' => array_keys($addressBlock->getChanges()),
            ],
        );

        return AddressBlockData::from($addressBlock);
    }

    public function destroy(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock): Response
    {
        Gate::authorize('delete', $addressBlock);

        $baseIp = $addressBlock->base_ip;

        $addressBlock->delete();

        Audit::record(
            AuditEvent::ADMIN_ADDRESS_BLOCK_DELETED,
            subject: $addressBlock,
            properties: ['base_ip' => $baseIp],
        );

        return response()->noContent();
    }
}
