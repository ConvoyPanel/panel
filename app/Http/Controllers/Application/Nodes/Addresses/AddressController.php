<?php

namespace App\Http\Controllers\Application\Nodes\Addresses;

use App\Http\Controllers\Controller;
use App\Models\IPAddress;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = QueryBuilder::for(IPAddress::query())
            ->allowedFilters(['server_id', 'node_id', 'address', 'cidr', 'gateway', 'type'])
            ->allowedSorts(['id', 'server_id', 'node_id'])
            ->paginate($request->query('per_page') ?? 50);

        return $addresses;
    }
}
