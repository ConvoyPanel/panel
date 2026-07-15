<?php

namespace App\Services\Isos;

use App\Data\Helpers\ChecksumData;
use App\Data\Node\Storage\IsoData;
use App\Enums\Node\Storage\StorageContentType;
use App\Jobs\Node\MonitorIsoDownloadJob;
use App\Models\ISO;
use App\Models\Node;
use App\Models\Storage;
use App\Services\Proxmox\Node\ProxmoxStorageClient;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IsoService
{
    public function __construct(
        private ConnectionInterface $connection,
        private ProxmoxStorageClient $client,
    ) {
    }

    public function download(
        Node $node,
        string $name,
        ?string $fileName,
        string $link,
        ?ChecksumData $checksumData = null,
        ?bool $hidden = false,
        ?Storage $storage = null,
    ) {
        // Default to an ISO-capable storage on the node; the caller may override.
        $storage ??= $node->isoStorage();
        if (is_null($storage)) {
            throw new BadRequestHttpException('No ISO-capable storage is configured for this node.');
        }

        $queriedFileMetadata = $this->client->setNode($node)->getFileMetadata($link);

        return $this->connection->transaction(
            function () use (
                $queriedFileMetadata,
                $node,
                $storage,
                $hidden,
                $fileName,
                $link,
                $name,
                $checksumData,
            ) {
                $iso = ISO::create([
                    'storage_id' => $storage->id,
                    'name' => $name,
                    'file_name' => $fileName ?? $queriedFileMetadata->fileName,
                    'hidden' => $hidden,
                    'size' => $queriedFileMetadata->size,
                ]);

                $upid = $this->client->setNode($node)->download(
                    StorageContentType::ISO,
                    $storage->name,
                    $iso->file_name,
                    $link,
                    true,
                    $checksumData,
                );

                MonitorIsoDownloadJob::dispatch($iso->id, $upid);

                return $iso;
            },
        );
    }

    public function getIso(Node $node, string $fileName): ?IsoData
    {
        $storage = $node->isoStorage();
        if (is_null($storage)) {
            return null;
        }

        $isos = $this->client->setNode($node)->getIsos($storage->name);

        return $isos->where('file_name', '=', $fileName)->first();
    }

    public function delete(Node $node, ISO $iso): void
    {
        if (is_null($iso->completed_at)) {
            throw new BadRequestHttpException(
                'This ISO cannot be deleted at this time: not completed.',
            );
        }

        $this->connection->transaction(function () use ($node, $iso) {
            if ($iso->is_successful) {
                $this->client->setNode($node)->deleteFile(StorageContentType::ISO, $iso->storage->name, $iso->file_name);
            }

            $iso->delete();
        });
    }
}
