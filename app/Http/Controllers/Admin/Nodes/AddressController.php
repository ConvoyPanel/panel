<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Http\Controllers\Controller;
use App\Models\Node;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AddressController extends Controller
{
    public function index(Node $node)
    {
        return Inertia::render('admin/nodes/addresses/Index', [
            'node' => $node
        ]);
    }
}
