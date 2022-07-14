<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard');
    }
}
