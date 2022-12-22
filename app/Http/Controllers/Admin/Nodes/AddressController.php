<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\Controller;
use Convoy\Models\IPAddress;
use Convoy\Transformers\Admin\AddressTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = QueryBuilder::for(IPAddress::query())
            ->allowedFilters([AllowedFilter::exact('node_id'), 'address', AllowedFilter::exact('type')])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($addresses, new AddressTransformer)->respond();
    }
}
