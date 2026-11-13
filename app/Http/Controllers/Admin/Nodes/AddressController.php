<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\Controller;
use Convoy\Models\Filters\FiltersAddressWildcard;
use Convoy\Models\Filters\FiltersExactId;
use Convoy\Models\Filters\FiltersExactIgnoringCase;
use Convoy\Models\Node;
use Convoy\Transformers\Admin\AddressTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController extends Controller
{
    public function index(Request $request, Node $node)
    {
        $addresses = QueryBuilder::for($node->addresses())
                                 ->with('server')
                                 ->defaultSort('-id')
                                 ->allowedFilters(
                                     ['address', AllowedFilter::custom(
                                         'type', new FiltersExactIgnoringCase(),
                                     ), AllowedFilter::custom(
                                         '*',
                                         new FiltersAddressWildcard(),
                                     ), AllowedFilter::custom('server_id', new FiltersExactId())->nullable()],
                                 )
                                 ->paginate(min($request->query('per_page', 50), 999999))->appends(
                $request->query(),
            );

        return fractal($addresses, new AddressTransformer())->parseIncludes($request->include)
                                                            ->respond();
    }
}
