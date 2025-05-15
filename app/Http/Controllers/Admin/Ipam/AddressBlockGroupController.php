<?php

namespace App\Http\Controllers\Admin\Ipam;

use App\Http\Requests\Admin\AddressBlockGroups\AddressBlockGroupRequest;
use App\Models\AddressBlockGroup;
use App\Models\Filters\FiltersAddressBlockGroupWildcard;
use App\Models\Filters\FiltersAddressBlockWildcard;
use App\Transformers\Admin\AddressBlockGroupTransformer;
use App\Transformers\Admin\AddressBlockTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        // TODO: implement destroy

        return response()->noContent();
    }
}
