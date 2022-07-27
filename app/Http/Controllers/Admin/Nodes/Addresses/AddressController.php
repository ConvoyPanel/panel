<?php

namespace App\Http\Controllers\Admin\Nodes\Addresses;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nodes\Addresses\StoreAddressRequest;
use App\Models\IPAddress;
use App\Models\Node;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AddressController extends ApplicationApiController
{
    public function index(Node $node)
    {
        return Inertia::render('admin/nodes/addresses/Index', [
            'node' => $node,
            'addresses' => $node->addresses,
        ]);
    }

    public function store(Node $node, StoreAddressRequest $request)
    {
        IPAddress::create($request->validated());

        return redirect()->route('admin.nodes.show.addresses.index', [$node->id]);
    }
}
