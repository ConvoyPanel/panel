<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\Controller;
use Inertia\Inertia;

class IndexController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/Index');
    }
}
