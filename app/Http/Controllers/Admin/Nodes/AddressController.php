<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Http\Controllers\Controller;
use App\Models\Filters\FiltersAddressWildcard;
use App\Models\Node;
use App\Transformers\Admin\AddressTransformer;
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
                                     ['address', AllowedFilter::exact(
                                         'type',
                                     ), AllowedFilter::custom(
                                         '*',
                                         new FiltersAddressWildcard(),
                                     ), AllowedFilter::exact('server_id')->nullable()],
                                 )
                                 ->paginate(min($request->query('per_page', 50), 100))->appends(
                                     $request->query(),
                                 );

        return fractal($addresses, new AddressTransformer())->parseIncludes($request->include)
                                                            ->respond();
    }
}
