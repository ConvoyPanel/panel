<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Data\Helpers\ChecksumData;
use Convoy\Enums\Helpers\ChecksumAlgorithm;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Admin\Nodes\Isos\StoreIsoRequest;
use Convoy\Http\Requests\Admin\Nodes\Isos\UpdateIsoRequest;
use Convoy\Models\ISO;
use Convoy\Models\Node;
use Convoy\Repositories\Proxmox\Node\ProxmoxStorageRepository;
use Convoy\Services\Nodes\Isos\IsoService;
use Convoy\Transformers\Admin\FileMetadataTransformer;
use Convoy\Transformers\Admin\IsoTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\QueryBuilder;

class IsoController extends ApplicationApiController
{
    public function __construct(private IsoService $isoService, private ProxmoxStorageRepository $repository)
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

    public function update(UpdateIsoRequest $request, Node $node, ISO $iso)
    {
        $iso->update($request->validated());

        return fractal($iso, new IsoTransformer)->respond();
    }

    public function destroy(Node $node, ISO $iso)
    {
        $this->isoService->delete($node, $iso);

        return $this->returnNoContent();
    }

    public function queryLink(Request $request, Node $node)
    {
        Validator::make([
            'link' => $request->link,
        ], [
            'link' => ['required', 'url'],
        ])->validate();

        $metadata = $this->repository->setNode($node)->getFileMetadata($request->link);

        return fractal($metadata, new FileMetadataTransformer)->respond();
    }
}
