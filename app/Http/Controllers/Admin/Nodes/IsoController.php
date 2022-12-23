<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Data\Helpers\ChecksumData;
use Convoy\Enums\Helpers\ChecksumAlgorithm;
use Convoy\Http\Controllers\Controller;
use Convoy\Http\Requests\Admin\Nodes\Isos\StoreIsoRequest;
use Convoy\Models\ISO;
use Convoy\Models\Node;
use Convoy\Services\Nodes\IsoLibrary\IsoService;
use Convoy\Transformers\Admin\IsoTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class IsoController extends Controller
{
    public function __construct(private IsoService $isoService)
    {
    }

    public function index(Node $node, Request $request)
    {
        $isos = QueryBuilder::for(ISO::query())
            ->where('iso_library.node_id', $node->id)
            ->allowedFilters(['name'])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($isos, new IsoTransformer)->respond();
    }

    public function store(StoreIsoRequest $request, Node $node)
    {
        $checksumData = (bool) $request->checksum_algorithum ? ChecksumData::from([
            'algorithm' => ChecksumAlgorithm::from($request->checksum_algorithum),
            'checksum' => $request->checksum,
        ]) : null;

        $iso = $this->isoService->create($node, $request->name, $request->file_name, $request->link, $checksumData, $request->hidden);

        return fractal($iso, new IsoTransformer)->respond();
    }
}
