<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;

class ServerAuthService
{
    public function __construct(private ProxmoxConfigRepository $configRepository)
    {
    }

    public function setPassword(Server $server, string $password): void
    {
        // Cloudinit now applies passwords on every supported OS (Windows included), so it's the
        // single source of truth here. The old QEMU-guest-agent live-set path (v4) was a more
        // fragile duplicate — it needed the agent running and OS-specific usernames — and is
        // deliberately not carried onto next.
        $this->configRepository->setServer($server)->update(['cipassword' => $password]);
    }

    public function getSSHKeys(Server $server): array
    {
        $raw = collect($this->configRepository->setServer($server)->getConfig())->where('key', '=', 'sshkeys')->first()['value'] ?? '';

        return array_values(array_filter(
            explode("\n", rawurldecode($raw)),
            fn (string $key) => trim($key) !== '',
        ));
    }

    public function setSSHKeys(Server $server, ?string $keys): void
    {
        if (! empty($keys)) {
            $this->configRepository->setServer($server)->update(['sshkeys' => rawurlencode($keys)]);
        } else {
            $this->configRepository->setServer($server)->update(['delete' => 'sshkeys']);
        }
    }
}
