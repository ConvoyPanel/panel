<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Http\Controllers\Controller;
use App\Models\Node;
use Illuminate\Http\Request;
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
