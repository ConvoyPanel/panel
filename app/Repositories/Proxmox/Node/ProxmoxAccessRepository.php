<?php

namespace Convoy\Repositories\Proxmox\Node;

use Carbon\Carbon;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Node;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Webmozart\Assert\Assert;

class ProxmoxAccessRepository extends ProxmoxRepository
{
    public function createUser(array $data = [])
    {
        Assert::isInstanceOf($this->node, Node::class);

        $payload = [
            'enable' => Arr::get($data, 'enable', true),
            'userid' => Arr::get($data, 'userid', 'convoy-'.Str::random(50)).'@'.Arr::get($data, 'authType', 'pve'),
            'password' => Arr::get($data, 'password', Str::random(60)),
            'expire' => Arr::get($data, 'expire', Carbon::now()->addDay()->timestamp),
        ];

        try {
            $this->getHttpClient()->post('/api2/json/access/users', [
                'json' => $payload,
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $payload;
    }

    public function createRole(string $name, string $privileges)
    {
        Assert::isInstanceOf($this->node, Node::class);

        $payload = [
            'roleid' => $name,
            'privs' => $privileges,
        ];

        try {
            $this->getHttpClient()->post('/api2/json/access/roles', [
                'json' => $payload,
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $payload;
    }

    public function getTicket(string $userid, string $password, string $realm = 'pve')
    {
        Assert::isInstanceOf($this->node, Node::class);

        try {
            $response = $this->getHttpClient(authorize: false)->post('/api2/json/access/ticket', [
                'json' => [
                    'username' => $userid,
                    'password' => $password,
                    'realm' => $realm,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }
}
