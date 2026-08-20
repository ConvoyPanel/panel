<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Helpers\ChecksumData;
use App\Data\Node\Storage\FileMetaData;
use App\Data\Node\Storage\IsoEloquentData;
use App\Data\PaginationMeta;
use App\Enums\Audit\AuditEvent;
use App\Enums\Helpers\ChecksumAlgorithm;
use App\Facades\Audit;
use App\Http\Requests\Admin\Nodes\Isos\StoreIsoRequest;
use App\Http\Requests\Admin\Nodes\Isos\UpdateIsoRequest;
use App\Models\ISO;
use App\Models\Node;
use App\Services\Isos\IsoService;
use App\Services\Proxmox\Node\ProxmoxStorageClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\QueryBuilder;

class IsoController
{
    public function __construct(
        private IsoService $isoService,
        private ProxmoxStorageClient $client,
    ) {}

    public function index(Node $node, Request $request)
    {
        $isos = QueryBuilder::for($node->isos())
            ->allowedFilters(['name'])
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return PaginationMeta::paginate($isos, IsoEloquentData::class);
    }

    public function store(StoreIsoRequest $request, Node $node)
    {
        $shouldDownload = $request->boolean('should_download');

        if ($shouldDownload) {
            $checksumData = (bool) $request->checksum_algorithum ? ChecksumData::from([
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

            $iso = ISO::create([
                'storage_id' => $node->isoStorage()->id,
                'is_successful' => true,
                'name' => $request->name,
                'file_name' => $request->file_name,
                'size' => $isoFromProxmox->size,
                'hidden' => $request->boolean('hidden'),
                'completed_at' => now(),
            ]);
        }

        Audit::record(
            AuditEvent::ADMIN_NODE_ISO_CREATED,
            subject: $node,
            properties: [
                'name' => $iso->name,
                'file_name' => $iso->file_name,
                'downloaded' => $shouldDownload,
            ],
        );

        return IsoEloquentData::from($iso);
    }

    public function update(UpdateIsoRequest $request, Node $node, ISO $iso)
    {
        $iso->update($request->validated());

        Audit::record(
            AuditEvent::ADMIN_NODE_ISO_UPDATED,
            subject: $node,
            properties: ['name' => $iso->name, 'changed' => array_keys($iso->getChanges())],
        );

        return IsoEloquentData::from($iso);
    }

    public function destroy(Node $node, ISO $iso)
    {
        $name = $iso->name;

        $this->isoService->delete($node, $iso);

        Audit::record(
            AuditEvent::ADMIN_NODE_ISO_DELETED,
            subject: $node,
            properties: ['name' => $name],
        );

        return response()->noContent();
    }

    public function queryLink(Request $request, Node $node)
    {
        Validator::make([
            'link' => $request->link,
        ], [
            'link' => ['required', 'url'],
        ])->validate();

        $metadata = $this->client->setNode($node)->getFileMetadata($request->link);

        return FileMetaData::from($metadata);
    }
}
