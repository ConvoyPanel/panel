<?php

namespace Convoy\Services\Servers;

use Convoy\Exceptions\DisplayException;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxGuestAgentRepository;
use Illuminate\Support\Str;

class ServerAuthService
{
    public function __construct(private ProxmoxConfigRepository $configRepository, private ProxmoxGuestAgentRepository $guestAgentRepository)
    {
    }

    /**
     * @param bool $requireGuestAgent Throw when the guest agent can't set the password. A build
     *                                leaves this off: its VM can still be stopped, and cloud-init
     *                                applies cipassword when it boots.
     */
    public function updatePassword(Server $server, string $password, bool $requireGuestAgent = false): void
    {
        // Always store CIPassword first
        $this->configRepository->setServer($server)->update(['cipassword' => $password]);

        try {
            $osInfo = $this->guestAgentRepository->setServer($server)->guestAgentOs();

            // If we have valid OS info, decide which username to use
            if (is_array($osInfo) && isset($osInfo['result']['name'])) {
                $osName = $osInfo['result']['name'];
                $username = Str::contains(Str::lower($osName), 'windows') ? 'Administrator' : 'root';

                $this->guestAgentRepository
                    ->setServer($server)
                    ->updateGuestAgentPassword($username, $password);
            }
        } catch (\Exception $e) {
            if ($requireGuestAgent) {
                throw new DisplayException(
                    "Couldn't set the password because the server's QEMU guest agent isn't running. Start the server and try again.",
                    $e,
                );
            }
        }
    }

    public function getSSHKeys(Server $server): string
    {
        $raw = collect($this->configRepository->setServer($server)->getConfig())->where('key', '=', 'sshkeys')->first()['value'] ?? '';

        return rawurldecode($raw);
    }

    public function updateSSHKeys(Server $server, ?string $keys): void
    {
        if (! empty($keys)) {
            $this->configRepository->setServer($server)->update(['sshkeys' => rawurlencode($keys)]);
        } else {
            $this->configRepository->setServer($server)->update(['delete' => 'sshkeys']);
        }
    }
}
