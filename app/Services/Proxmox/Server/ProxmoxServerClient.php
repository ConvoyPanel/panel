<?php

namespace App\Services\Proxmox\Server;

use App\Data\Server\Proxmox\ServerStateData;
use App\Enums\Image\ImageDiskRole;
use App\Enums\Node\Access\RealmType;
use App\Exceptions\Proxmox\RequestException;
use App\Models\ImageVersion;
use App\Services\Images\OsProfiles;
use App\Services\Nodes\GuestStateCache;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ProxmoxServerClient extends ProxmoxClient
{
    public function __construct(private GuestStateCache $guestStates) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getState(): ServerStateData
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/status/current')
            ->json();

        $state = ServerStateData::fromRaw($this->getData($response));

        // Write-through: this is the only place in the app that reads one
        // guest's status live, so recording it here means no caller can forget
        // to. It is what keeps a server list -- which reads the cache and never
        // PVE -- from showing a stale badge in the minute after a power action,
        // and it costs one cache write on a request that just paid for a round
        // trip to Proxmox.
        $this->guestStates->observe($this->getServer(), $state->powerState);

        return $state;
    }

    /**
     * @return string Job UPID
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    /**
     * Build the guest from an image version rather than cloning a template.
     *
     * A clone inherited every hardware setting from the template's own config,
     * which is why the panel never had to store any. There is nothing to
     * inherit here: the VM is assembled entirely from arguments, so this call
     * carries the whole profile and is the one place the definition's hardware
     * becomes a real machine.
     *
     * @param  array<string, string>  $volids  Import sources on the node, keyed by disk role.
     * @return string Job UPID
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function create(ImageVersion $version, array $volids): string
    {
        $server = $this->getServer();
        $definition = $version->definition;
        $hardware = $definition->effectiveHardware();
        $storage = $server->storage->name;

        $bootSlot = $hardware['boot_disk_slot'] ?? 'scsi0';
        $system = $volids[ImageDiskRole::SYSTEM->value]
            ?? throw new ConflictHttpException('This image version has no system disk to import.');

        $payload = array_merge(OsProfiles::proxmoxKeys($hardware), [
            'vmid' => $server->vmid,
            'name' => $server->hostname,
            // Its own column, and passed explicitly: Proxmox derives the
            // cloud-init drive type from it, so it must not be left to whatever
            // happens to be in the profile blob.
            'ostype' => $definition->ostype,
            'cores' => $server->cpu,
            'memory' => (int) ($server->memory / 1024 / 1024),

            // Size 0 means "take the source's size". The imported disk arrives
            // at the image's own virtual size and is grown to the plan
            // afterwards, because Proxmox can grow a disk and cannot shrink one.
            $bootSlot => "{$storage}:0,import-from={$system}",

            // Without this the guest can come up on an empty NIC or the
            // cloud-init drive; a clone used to inherit a boot order.
            'boot' => $hardware['boot'] ?? "order={$bootSlot}",
        ]);

        // Only OVMF images carry a varstore, and it is shipped verbatim rather
        // than regenerated: it holds the boot entry and the enrolled Secure
        // Boot keys, so a fresh one would leave Windows unbootable.
        if (isset($volids[ImageDiskRole::EFIVARS->value])) {
            $payload['efidisk0'] = sprintf(
                '%s:0,import-from=%s,efitype=4m',
                $storage,
                $volids[ImageDiskRole::EFIVARS->value],
            );
        }

        if (filled($cloudinitSlot = $hardware['cloudinit_slot'] ?? 'ide2')) {
            $payload[$cloudinitSlot] = "{$storage}:cloudinit";
        }

        $response = $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu', $payload)
            ->json();

        return $this->getData($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function delete()
    {
        $response = $this->getHttpClientWithParams()
//            ->withOptions([
//                'query' => [
//                    'destroy-unreferenced-disks' => true,
//                    'purge' => true,
//                ],
//            ])
            ->delete('/api2/json/nodes/{node}/qemu/{server}')
            ->json();

        return $this->getData($response);
    }

    public function addUser(RealmType $realmType, string $userId, string $roleId)
    {
        $response = $this->getHttpClient()
            ->put('/api2/json/access/acl', [
                'path' => '/vms/'.$this->server->vmid,
                'users' => $userId.'@'.$realmType->value,
                'roles' => $roleId,
            ])
            ->json();

        return $this->getData($response);
    }
}
