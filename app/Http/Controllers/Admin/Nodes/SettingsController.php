<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\Controller;
use Convoy\Models\Node;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Node $node)
    {
        return Inertia::render('admin/nodes/settings/Index', [
            'node' => $node,
        ]);
    }
}
