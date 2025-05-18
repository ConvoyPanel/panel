<?php

namespace App\Http\Controllers\Admin\Ipam;

use App\Http\Requests\Admin\AddressBlocks\StoreAddressBlockRequest;
use App\Http\Requests\Admin\AddressBlocks\UpdateAddressBlockRequest;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Filters\FiltersAddressBlockWildcard;
use App\Transformers\Admin\AddressBlockTransformer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class AddressBlockController
{
    public function __construct(private ConnectionInterface $connection) {}

    public function index(Request $request, AddressBlockGroup $addressBlockGroup): JsonResponse
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

        return fractal($blocks, new AddressBlockTransformer)->respond();
    }

    public function show(
        AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock,
    ): JsonResponse {
        return fractal($addressBlock, new AddressBlockTransformer)->respond();
    }

    public function store(StoreAddressBlockRequest $request, AddressBlockGroup $addressBlockGroup,
    ): JsonResponse {
        $block = $addressBlockGroup->addressBlocks()->create($request->validated());

        return fractal($block, new AddressBlockTransformer)->respond();
    }

    /**
     * @throws Throwable
     */
    public function update(
        UpdateAddressBlockRequest $request, AddressBlockGroup $addressBlockGroup,
        AddressBlock $addressBlock,
    ): JsonResponse {
        // TODO: batch sync server network configs if gateway or mac address is changed

        $this->connection->transaction(
            function () use ($request, $addressBlock) {
                if (
                    $addressBlock->base_ip !== $request->string('base_ip') ||
                    $addressBlock->prefix_length_from !== $request->integer('prefix_length_from') ||
                    $addressBlock->prefix_length_to !== $request->integer('prefix_length_to')
                ) {
                    $addressBlock->addresses()->delete();
                }

                $addressBlock->update($request->validated());
            },
        );

        return fractal($addressBlock, new AddressBlockTransformer)->respond();
    }

    public function destroy(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock): Response
    {
        // TODO: implement destroy

        return response()->noContent();
    }
}
