<?php

namespace App\Http\Controllers\Admin;

use App\Models\AddressBlockGroup;
use App\Models\Filters\FiltersAddressBlockGroupWildcard;
use App\Models\Filters\FiltersAddressBlockWildcard;
use App\Transformers\Admin\AddressBlockGroupTransformer;
use App\Transformers\Admin\AddressBlockTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function showAddressBlocks(Request $request, AddressBlockGroup $addressBlockGroup): JsonResponse
    {
        $blocks = QueryBuilder::for($addressBlockGroup->addressBlocks())
            ->defaultSort('-id')
            ->allowedFilters(
                AllowedFilter::custom('*', new FiltersAddressBlockWildcard),
                'name',
                'description',
                AllowedFilter::exact('type'),
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
}
