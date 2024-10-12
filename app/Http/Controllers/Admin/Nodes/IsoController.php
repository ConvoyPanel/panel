<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Helpers\ChecksumData;
use App\Enums\Helpers\ChecksumAlgorithm;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\Nodes\Isos\StoreIsoRequest;
use App\Http\Requests\Admin\Nodes\Isos\UpdateIsoRequest;
use App\Models\ISO;
use App\Models\Node;
use App\Repositories\Proxmox\Node\ProxmoxStorageRepository;
use App\Services\Isos\IsoService;
use App\Transformers\Admin\FileMetadataTransformer;
use App\Transformers\Admin\IsoTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\QueryBuilder;

class IsoController extends ApiController
{
    public function __construct(
        private IsoService $isoService,
        private ProxmoxStorageRepository $repository,
    ) {
    }

    public function index(Node $node, Request $request)
    {
        $isos = QueryBuilder::for(ISO::query())
                            ->where('iso_library.node_id', $node->id)
                            ->allowedFilters(['name'])
                            ->paginate(min($request->query('per_page', 50), 100))->appends(
                                $request->query(),
                            );

        return fractal($isos, new IsoTransformer())->respond();
    }

    public function store(StoreIsoRequest $request, Node $node)
    {
        $shouldDownload = $request->boolean('should_download');

        if ($shouldDownload) {
            $checksumData = (bool)$request->checksum_algorithum ? ChecksumData::from([
                'algorithm' => ChecksumAlgorithm::from($request->checksum_algorithum),
                'checksum' => $request->checksum,
            ]) : null;

            $iso = $this->isoService->download(
                $node,
                $request->name,
                $request->file_name,
                $request->link,
                $checksumData,
                $request->hidden,
            );
        } else {
            $isoFromProxmox = $this->isoService->getIso($node, $request->file_name);

            $iso = $node->isos()->create([
                'is_successful' => true,
                'name' => $request->name,
                'file_name' => $request->file_name,
                'size' => $isoFromProxmox->size,
                'hidden' => $request->boolean('hidden'),
                'completed_at' => now(),
            ]);
        }

        return fractal($iso, new IsoTransformer())->respond();
    }

    public function update(UpdateIsoRequest $request, Node $node, ISO $iso)
    {
        $iso->update($request->validated());

        return fractal($iso, new IsoTransformer())->respond();
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

        return fractal($metadata, new FileMetadataTransformer())->respond();
    }
}
