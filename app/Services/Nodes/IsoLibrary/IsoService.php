<?php

namespace Convoy\Services\Nodes\IsoLibrary;

use Convoy\Data\Helpers\ChecksumData;
use Convoy\Enums\Node\Storage\ContentType;
use Convoy\Jobs\Node\MonitorIsoDownloadJob;
use Convoy\Models\ISO;
use Convoy\Models\Node;
use Convoy\Repositories\Proxmox\Node\ProxmoxStorageRepository;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;

class IsoService
{
    public function __construct(private ConnectionInterface $connection, private ProxmoxStorageRepository $repository)
    {
    }
    public function create(Node $node, string $name, ?string $fileName = null, string $link, ?ChecksumData $checksumData = null, ?bool $hidden = false)
    {
        $queriedFileMetadata = $this->repository->getFileMetadata($link);

        return $this->connection->transaction(function () use ($queriedFileMetadata, $node, $hidden, $fileName, $link, $name, $checksumData) {
            $iso = ISO::create([
                'uuid' => Uuid::uuid4()->toString(),
                'node_id' => $node->id,
                'name' => $name,
                'file_name' => $fileName ?? $queriedFileMetadata->file_name,
                'hidden' => $hidden,
                'size' => $queriedFileMetadata->size,
            ]);

            $upid = $this->repository->setNode($node)->download(ContentType::ISO, $iso->file_name, $link, true, $checksumData);

            MonitorIsoDownloadJob::dispatch($iso->id, $upid);

            return $iso;
        });
    }
}
