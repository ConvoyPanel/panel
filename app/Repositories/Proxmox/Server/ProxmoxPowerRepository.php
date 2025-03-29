<?php

namespace App\Repositories\Proxmox\Server;

use App\Enums\Server\PowerAction;
use App\Repositories\Proxmox\ProxmoxRepository;

class ProxmoxPowerRepository extends ProxmoxRepository
{
    public function send(PowerAction $action)
    {
        // I added this because I don't like the naming scheme Proxmox has
        $parsedAction = match ($action) {
            PowerAction::RESTART => 'reboot',
            PowerAction::RESET => 'reset',
            PowerAction::RESUME => 'resume',
            PowerAction::SHUTDOWN => 'shutdown',
            PowerAction::START => 'start',
            PowerAction::KILL => 'stop',
            PowerAction::SUSPEND => 'suspend',
        };

        $response = $this->getHttpClientWithParams([
            'action' => $parsedAction,
        ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/status/{action}', [
                ...($parsedAction !== 'suspend' ? ['timeout' => 30] : ['skiplock' => false]),
            ])
            ->json();

        return $this->getData($response);
    }
}
