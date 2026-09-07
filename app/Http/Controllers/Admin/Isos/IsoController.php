<?php

namespace App\Http\Controllers\Admin\Isos;

use App\Data\Node\Storage\FileMetaData;
use App\Data\Node\Storage\IsoEloquentData;
use App\Data\PaginationMeta;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Isos\StoreIsoRequest;
use App\Http\Requests\Admin\Isos\UpdateIsoRequest;
use App\Models\ISO;
use App\Models\Node;
use App\Services\Isos\IsoService;
use App\Services\Proxmox\Node\ProxmoxStorageClient;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * The ISO library, which belongs to the panel rather than to a node.
 *
 * Adding one no longer involves choosing where it goes: an operator says what
 * it is and where the bytes are, and the first node to mount it fetches it.
 */
class IsoController
{
    public function __construct(
        private IsoService $isos,
        private ProxmoxStorageClient $client,
    ) {}

    public function index(Request $request)
    {
        $isos = QueryBuilder::for(ISO::query())
            ->allowedFilters(['name'])
            ->defaultSort('name')
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($isos, IsoEloquentData::class);
    }

    public function store(StoreIsoRequest $request)
    {
        $iso = $this->isos->create($request->validated());

        Audit::record(
            AuditEvent::ADMIN_ISO_CREATED,
            subject: $iso,
            properties: [
                'name' => $iso->name,
                'file_name' => $iso->file_name,
                'hosted' => $iso->isHosted(),
            ],
        );

        return IsoEloquentData::from($iso);
    }

    public function show(ISO $iso)
    {
        return IsoEloquentData::from($iso);
    }

    public function update(UpdateIsoRequest $request, ISO $iso)
    {
        $iso->update($request->validated());

        Audit::record(
            AuditEvent::ADMIN_ISO_UPDATED,
            subject: $iso,
            properties: ['name' => $iso->name, 'changed' => array_keys($iso->getChanges())],
        );

        return IsoEloquentData::from($iso);
    }

    /**
     * What a URL points at, so the add form can fill in its own blanks.
     *
     * Proxmox is the one that can answer this -- it is a node-side probe -- so
     * any node capable of holding ISOs will do. The library is panel-wide, so
     * which node answered has no bearing on the record that results.
     */
    public function queryLink(Request $request)
    {
        $request->validate(['link' => ['required', 'url']]);

        $node = Node::query()
            ->whereHas('storages', fn ($storages) => $storages->where('stores_iso', true))
            ->first() ?? throw new ConflictHttpException(
                'No node has ISO storage, so Convoy cannot inspect that link.',
            );

        return FileMetaData::from(
            $this->client->setNode($node)->getFileMetadata($request->string('link')->toString()),
        );
    }

    public function destroy(ISO $iso)
    {
        $name = $iso->name;

        $this->isos->delete($iso);

        Audit::record(
            AuditEvent::ADMIN_ISO_DELETED,
            subject: $iso,
            properties: ['name' => $name],
        );

        return response()->noContent();
    }
}
