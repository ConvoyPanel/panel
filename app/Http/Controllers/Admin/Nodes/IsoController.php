<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\Controller;
use Convoy\Models\ISO;
use Convoy\Models\Node;
use Convoy\Transformers\Admin\IsoTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class IsoController extends Controller
{
    public function index(Node $node, Request $request)
    {
        $isos = QueryBuilder::for(ISO::query())
            ->where('iso_library.node_id', $node->id)
            ->allowedFilters(['name', 'hostname'])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($isos, new IsoTransformer)->respond();
    }
}
