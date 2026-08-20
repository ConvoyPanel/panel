<?php

namespace App\Services\Servers;

use App\Exceptions\Proxmox\RequestException;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxConfigClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Str;

readonly class VmSyncService
{
    public function __construct(
        private AllocationService $allocationService,
        private CloudinitService $cloudinitService,
        private ServerNetworkService $networkService,
        private ProxmoxConfigClient $configClient,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(Server $server, ?callable $onProgress = null): void
    {
        if ($onProgress === null) {
            $onProgress = fn () => null;
        }

        $this->stampIdentity($server);

        $this->allocationService->syncSettings($server);
        $onProgress();

        // Materialize any secondary data disks (each on its own storage) after
        // the primary/clone is in place.
        $this->allocationService->syncDisks($server);

        $this->cloudinitService->setHostname($server, $server->hostname);
        $onProgress();

        $this->networkService->syncSettings($server);
        $onProgress();
    }

    /**
     * Writes the server's identity into the guest's `smbios1` config, where it
     * travels with the config file through migrations and HA recovery and lets
     * the placement reconciler confirm "same guest, new node" before touching
     * `node_id` (see ServerPlacementService).
     *
     * Runs on every build and rebuild: a rebuild is a fresh clone carrying a
     * freshly generated PVE uuid, so the stored value is re-asserted each time
     * rather than only minted once. Only the uuid field is replaced -- a
     * template may carry other smbios1 fields (manufacturer branding and the
     * like) that are its own business.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    private function stampIdentity(Server $server): void
    {
        $uuid = $server->smbios_uuid ?? Str::lower(Str::uuid()->toString());

        $config = $this->configClient->setServer($server)->getRawConfig();

        $fields = array_values(array_filter(
            explode(',', $config['smbios1'] ?? ''),
            fn (string $field) => $field !== '' && ! Str::startsWith($field, 'uuid='),
        ));

        array_unshift($fields, "uuid={$uuid}");

        $this->configClient->update(['smbios1' => implode(',', $fields)]);

        if ($server->smbios_uuid !== $uuid) {
            $server->forceFill(['smbios_uuid' => $uuid])->save();
        }
    }
}
