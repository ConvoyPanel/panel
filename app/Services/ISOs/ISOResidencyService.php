<?php

namespace App\Services\ISOs;

use App\Data\Helpers\ChecksumData;
use App\Enums\Helpers\ChecksumAlgorithm;
use App\Enums\Node\Storage\StorageContentType;
use App\Exceptions\Proxmox\RequestException;
use App\Models\ISO;
use App\Models\Node;
use App\Models\Storage;
use App\Services\Images\ImageSourceResolver;
use App\Services\Proxmox\Node\ProxmoxStorageClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Storage as Filesystem;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Gets an ISO onto the node that is about to mount it.
 *
 * The library says an ISO exists; this makes it exist *here*. Splitting the two
 * is what removes placement from the admin's job: adding an ISO no longer means
 * choosing which nodes get it, and a node added next month is not missing
 * anything. The transfer happens once per node, the first time someone mounts
 * it there.
 *
 * Proxmox does the downloading and, when the library has a hash, the verifying
 * -- so several gigabytes never pass through the panel and a substituted file
 * is the node's refusal rather than the panel's problem.
 */
class ISOResidencyService
{
    public function __construct(
        private ProxmoxStorageClient $client,
        private ImageSourceResolver $resolver,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function isResident(Node $node, ISO $iso): bool
    {
        $names = $this->client->setNode($node)->getFileNames(
            StorageContentType::ISO,
            $this->storageFor($node)->name,
        );

        return in_array($iso->file_name, $names, true);
    }

    /**
     * Start the download if this node does not have the file yet.
     *
     * @return ?string Task UPID, or null when the node already has it
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function ensureResident(Node $node, ISO $iso): ?string
    {
        if ($this->isResident($node, $iso)) {
            return null;
        }

        return $this->client->setNode($node)->download(
            contentType: StorageContentType::ISO,
            storage: $this->storageFor($node)->name,
            fileName: $iso->file_name,
            link: $this->urlFor($iso),
            checksumData: filled($iso->sha256)
                ? new ChecksumData(checksum: $iso->sha256, algorithm: ChecksumAlgorithm::SHA256)
                : null,
        );
    }

    /**
     * The volume string a mounted copy takes on this node.
     */
    public function volume(Node $node, ISO $iso): string
    {
        return sprintf(
            '%s:%s/%s',
            $this->storageFor($node)->name,
            StorageContentType::ISO->toProxmoxString(),
            $iso->file_name,
        );
    }

    /**
     * A link the operator gave us, or a signed one for a file we host.
     */
    private function urlFor(ISO $iso): string
    {
        if (! $iso->isHosted()) {
            return $iso->url ?? throw new ConflictHttpException(
                'This ISO has neither a URL nor an uploaded file.',
            );
        }

        return Filesystem::disk($this->resolver->diskName())->temporaryUrl(
            (string) $iso->path,
            now()->addMinutes((int) config('convoy.artifacts.url_ttl_minutes', 120)),
        );
    }

    private function storageFor(Node $node): Storage
    {
        return $node->isoStorage() ?? throw new ConflictHttpException(
            "No storage on {$node->name} accepts ISOs.",
        );
    }
}
