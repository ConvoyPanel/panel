<?php

namespace App\Repositories\Proxmox\Server;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Node;
use App\Models\Server;
use App\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxAllocationRepository extends ProxmoxRepository
{
    public $diskTypes = [
        'scsi',
        'sata',
        'virtio',
        'ide',
    ];

    public $validDisks = ['ide0', 'ide1', 'ide2', 'ide3', 'scsi0', 'scsi1', 'scsi2', 'scsi3', 'scsi4', 'scsi5', 'scsi6', 'scsi7', 'scsi8', 'scsi9', 'scsi10', 'scsi11', 'scsi12', 'scsi13', 'scsi14', 'scsi15', 'scsi16', 'scsi17', 'scsi18', 'scsi19', 'scsi20', 'scsi21', 'scsi22', 'scsi23', 'scsi24', 'scsi25', 'scsi26', 'scsi27', 'scsi28', 'scsi29', 'scsi30', 'virtio0', 'virtio1', 'virtio2', 'virtio3', 'virtio4', 'virtio5', 'virtio6', 'virtio7', 'virtio8', 'virtio9', 'virtio10', 'virtio11', 'virtio12', 'virtio13', 'virtio14', 'virtio15', 'sata0', 'sata1', 'sata2', 'sata3', 'sata4', 'sata5', 'efidisk0', 'tpmstate0'];

    public function getAllocations()
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/qemu/%s/pending', $this->node->cluster, $this->server->vmid));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException();
        }

        $json = json_decode($response->getBody(), true);
        return $json['data'] ?? $json;
    }

    public function update(array $params = [])
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu/%s/config', $this->node->cluster, $this->server->vmid),
            [
                'json' => $params
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException();
        }

        $json = json_decode($response->getBody(), true);
        return $json['data'] ?? $json;
    }

    public function resizeDisk(int $bytes, string $disk)
    {
        Assert::isInstanceOf($this->server, Server::class);
        Assert::inArray($disk, $this->validDisks, 'Invalid disk type');

        $gigabytes = $bytes / 1073741824;

        try {
            $response = $this->getHttpClient()->put(sprintf('/api2/json/nodes/%s/qemu/%s/resize', $this->node->cluster, $this->server->vmid),
                [
                    'json' => [
                        'disk' => $disk,
                        'size' => "+{$gigabytes}G"
                    ]
                ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        $json = json_decode($response->getBody(), true);
        return $json['data'] ?? $json;
    }
}