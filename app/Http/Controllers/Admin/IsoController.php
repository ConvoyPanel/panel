<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\Controller;
use Convoy\Models\ISO;
use Convoy\Transformers\Admin\IsoTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class IsoController extends Controller
{
    public function index(Request $request)
    {
        $isos = QueryBuilder::for(ISO::query())
            ->with('servers')
            ->withCount(['servers'])
            ->allowedFilters(['name', 'hostname'])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($isos, new IsoTransformer)->respond();
    }
}
