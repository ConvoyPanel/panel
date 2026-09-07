<?php

namespace App\Services\Images;

use App\Data\Helpers\ChecksumData;
use App\Data\Image\ImageDiskData;
use App\Enums\Helpers\ChecksumAlgorithm;
use App\Enums\Node\Storage\StorageContentType;
use App\Exceptions\Proxmox\RequestException;
use App\Models\ImageVersion;
use App\Models\Node;
use App\Services\Proxmox\Node\ProxmoxStorageClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Gets an image version's disks onto a node, once.
 *
 * `qm create --import-from` reads a file that is already on the node, so a
 * build has two halves: put the bytes there, then create from them. This owns
 * the first half, and it is the expensive one -- a Windows image is ~10 GB.
 *
 * The cost is per node per version, never per server: the file is named after
 * its own hash, so the second server built from an image finds it already there
 * and this does nothing. That is also why the transfer can be paid up front,
 * when an image is enabled, rather than by whoever happens to order first.
 *
 * Proxmox does the downloading. Its `download-url` endpoint verifies the
 * checksum itself, which keeps the panel out of the path of several gigabytes
 * and means a corrupted or substituted file is rejected by the node rather than
 * trusted because it arrived over TLS.
 */
class ImageResidencyService
{
    public function __construct(
        private ProxmoxStorageClient $client,
        private ImageSourceResolver $resolver,
    ) {}

    /**
     * Where each disk of this version lives on the node, keyed by role.
     *
     * These are volids, the form `import-from` takes when the file is in an
     * import-content storage. Callers use them without checking residency
     * first; `ensureResident` is what makes them true.
     *
     * @return array<string, string>
     */
    public function volids(Node $node, ImageVersion $version): array
    {
        $storage = $this->storageFor($node);

        return $version->diskSet()
            ->mapWithKeys(fn (ImageDiskData $disk) => [
                $disk->role->value => sprintf(
                    '%s:%s/%s',
                    $storage->name,
                    StorageContentType::IMPORT->toProxmoxString(),
                    $this->resolver->fileNameFor($disk),
                ),
            ])
            ->all();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function isResident(Node $node, ImageVersion $version): bool
    {
        return $this->missingDisks($node, $version)->isEmpty();
    }

    /**
     * Start a download for every disk this node is missing.
     *
     * @return array<int, string> Task UPIDs, empty when the node already has everything
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function ensureResident(Node $node, ImageVersion $version): array
    {
        $storage = $this->storageFor($node);

        return $this->missingDisks($node, $version)
            ->map(fn (ImageDiskData $disk) => $this->client->setNode($node)->download(
                contentType: StorageContentType::IMPORT,
                storage: $storage->name,
                fileName: $this->resolver->fileNameFor($disk),
                link: $this->resolver->urlFor($disk),
                checksumData: new ChecksumData(
                    checksum: $disk->sha256,
                    algorithm: ChecksumAlgorithm::SHA256,
                ),
            ))
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, ImageDiskData>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    private function missingDisks(Node $node, ImageVersion $version)
    {
        $storage = $this->storageFor($node);

        $present = $this->client->setNode($node)->getFileNames(
            StorageContentType::IMPORT,
            $storage->name,
        );

        return $version->diskSet()->reject(
            fn (ImageDiskData $disk) => in_array($this->resolver->fileNameFor($disk), $present, true),
        );
    }

    /**
     * PVE keeps the `import` content type off by default, so this is a real and
     * common configuration gap rather than a defensive null check. Saying which
     * node and what to enable is the difference between an admin fixing it in a
     * minute and filing a bug about builds hanging.
     */
    private function storageFor(Node $node)
    {
        return $node->importStorage() ?? throw new ConflictHttpException(
            "No storage on {$node->name} accepts disk images. Add `Import` to a storage's content types in Proxmox.",
        );
    }
}
