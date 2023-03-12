<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxConfigRepository extends ProxmoxRepository
{
    public static $validDisks = ['ide0', 'ide1', 'ide2', 'ide3', 'scsi0', 'scsi1', 'scsi2', 'scsi3', 'scsi4', 'scsi5', 'scsi6', 'scsi7', 'scsi8', 'scsi9', 'scsi10', 'scsi11', 'scsi12', 'scsi13', 'scsi14', 'scsi15', 'scsi16', 'scsi17', 'scsi18', 'scsi19', 'scsi20', 'scsi21', 'scsi22', 'scsi23', 'scsi24', 'scsi25', 'scsi26', 'scsi27', 'scsi28', 'scsi29', 'scsi30', 'virtio0', 'virtio1', 'virtio2', 'virtio3', 'virtio4', 'virtio5', 'virtio6', 'virtio7', 'virtio8', 'virtio9', 'virtio10', 'virtio11', 'virtio12', 'virtio13', 'virtio14', 'virtio15', 'sata0', 'sata1', 'sata2', 'sata3', 'sata4', 'sata5', 'efidisk0', 'tpmstate0'];

    public function getConfig()
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid
            ])
            ->get('/api2/json/nodes/{node}/qemu/{server}/config')
            ->json();


        $unparsed = $this->getData($response);
        $parsed = [];

        foreach ($unparsed as $key => $value ) {
            $parsed[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        return $parsed;
    }

    public function update(array $payload = [], bool $put = false)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid
            ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/config', $payload)
            ->json();

        return $this->getData($response);
    }
}
