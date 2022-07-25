<?php

namespace App\Http\Controllers\Admin\Servers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServerController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/servers/Index');
    }
}
