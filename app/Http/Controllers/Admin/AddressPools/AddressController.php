<?php

namespace Convoy\Http\Controllers\Admin\AddressPools;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\AddressPool;
use Convoy\Models\Filters\AllowedNullableFilter;
use Convoy\Models\Filters\FiltersAddress;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController extends ApplicationApiController
{
    public function index(Request $request, AddressPool $addressPool)
    {
        $addresses = QueryBuilder::for($addressPool->addresses())
            ->with('server')
            ->allowedFilters(['address', AllowedFilter::exact('type'), AllowedFilter::custom('*', new FiltersAddress), AllowedNullableFilter::exact('server_id')])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());


    }
}
