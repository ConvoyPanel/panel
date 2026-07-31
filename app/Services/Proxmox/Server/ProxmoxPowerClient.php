<?php

namespace App\Services\Proxmox\Server;

use App\Enums\Server\PowerCommand;
use App\Exceptions\Proxmox\RequestException;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;

class ProxmoxPowerClient extends ProxmoxClient
{
    /**
     * Issue the command and return the UPID of the Proxmox task it spawned, so
     * the caller can track the action to completion.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function send(PowerCommand $action): ?string
    {
        // I added this because I don't like the naming scheme Proxmox has
        $parsedAction = match ($action) {
            PowerCommand::RESTART => 'reboot',
            PowerCommand::RESET => 'reset',
            PowerCommand::RESUME => 'resume',
            PowerCommand::SHUTDOWN => 'shutdown',
            PowerCommand::START => 'start',
            PowerCommand::KILL => 'stop',
            PowerCommand::SUSPEND => 'suspend',
        };

        $response = $this->getHttpClientWithParams([
            'action' => $parsedAction,
        ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/status/{action}', [
                ...($parsedAction !== 'suspend' ? ['timeout' => 30] : ['skiplock' => false]),
            ])
            ->json();

        $upid = $this->getData($response);

        return is_string($upid) ? $upid : null;
    }
}
