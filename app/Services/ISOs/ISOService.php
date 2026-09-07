<?php

namespace App\Services\ISOs;

use App\Enums\Node\Storage\StorageContentType;
use App\Models\ISO;
use App\Models\Node;
use App\Services\Images\ImageSourceResolver;
use App\Services\Proxmox\Node\ProxmoxStorageClient;
use Illuminate\Support\Facades\Storage as Filesystem;

/**
 * The ISO library: what the panel offers, not what any node holds.
 *
 * Adding an ISO is now a panel-level act with no node in it -- the operator
 * gives a URL or uploads a file, and that is the whole operation. Getting it
 * onto a node is {@see ISOResidencyService}, and it happens when someone mounts
 * it.
 */
class ISOService
{
    public function __construct(
        private ProxmoxStorageClient $client,
        private ImageSourceResolver $resolver,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ISO
    {
        return ISO::create($attributes);
    }

    /**
     * Remove an ISO from the library, and the file if the panel was hosting it.
     *
     * Copies already sitting on nodes are deliberately left alone. Deleting
     * them would mean reaching into every node the panel knows about, some of
     * which will be unreachable, to reclaim space PVE already treats as a cache
     * -- and a half-completed sweep is worse than none. They are ordinary ISO
     * files an operator can prune from Proxmox.
     */
    public function delete(ISO $iso): void
    {
        if ($iso->isHosted()) {
            Filesystem::disk($this->resolver->diskName())->delete((string) $iso->path);
        }

        $iso->delete();
    }

    /**
     * File names already on a node's ISO storage.
     *
     * Used by the admin UI to offer ISOs a node happens to hold, so an operator
     * who uploaded one to Proxmox by hand can register it without re-uploading.
     *
     * @return array<int, string>
     */
    public function fileNamesOn(Node $node): array
    {
        $storage = $node->isoStorage();

        if (is_null($storage)) {
            return [];
        }

        return $this->client->setNode($node)->getFileNames(
            StorageContentType::ISO,
            $storage->name,
        );
    }
}
