<?php

namespace App\Services\Isos;

use App\Data\Helpers\ChecksumData;
use App\Data\Node\Storage\IsoData;
use App\Enums\Node\Storage\ContentType;
use App\Jobs\Node\MonitorIsoDownloadJob;
use App\Models\ISO;
use App\Models\Node;
use App\Repositories\Proxmox\Node\ProxmoxStorageRepository;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IsoService
{
    public function __construct(
        private ConnectionInterface $connection,
        private ProxmoxStorageRepository $repository,
    ) {
    }

    public function download(
        Node $node,
        string $name,
        ?string $fileName,
        string $link,
        ?ChecksumData $checksumData = null,
        ?bool $hidden = false,
    ) {
        $queriedFileMetadata = $this->repository->setNode($node)->getFileMetadata($link);

        return $this->connection->transaction(
            function () use (
                $queriedFileMetadata,
                $node,
                $hidden,
                $fileName,
                $link,
                $name,
                $checksumData,
            ) {
                $iso = ISO::create([
                    'node_id' => $node->id,
                    'name' => $name,
                    'file_name' => $fileName ?? $queriedFileMetadata->file_name,
                    'hidden' => $hidden,
                    'size' => $queriedFileMetadata->size,
                ]);

                $upid = $this->repository->setNode($node)->download(
                    ContentType::ISO,
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
        $isos = $this->repository->setNode($node)->getIsos();

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
                $this->repository->setNode($node)->deleteFile(ContentType::ISO, $iso->file_name);
            }

            $iso->delete();
        });
    }
}
