<?php

namespace App\Services\Proxmox\Node;

use App\Data\Node\Access\CreateUserData;
use App\Data\Node\Access\UserCredentialsData;
use App\Data\Node\Access\UserData;
use App\Enums\Node\Access\RealmType;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;

class ProxmoxAccessClient extends ProxmoxClient
{
    public function getUsers(): DataCollection
    {
        $response = $this->getHttpClient()
            ->get('/api2/json/access/users')
            ->json();

        $users = array_map(fn ($user) => UserData::fromRaw($user), $this->getData($response));

        return UserData::collect($users, DataCollection::class);
    }

    public function createUser(CreateUserData $data): CreateUserData
    {
        $payload = [
            'enable' => $data->enabled,
            'userid' => ($data->username ?? 'convoy-'.Str::random(53)).'@'.$data->realmType->value,
            'password' => $data->password ?? Str::random(64),
            'expire' => $data->expiresAt->timestamp ?? false,
        ];

        $this->getHttpClient()
            ->post('/api2/json/access/users', $payload)
            ->json();

        return CreateUserData::from([
            'username' => explode('@', $payload['userid'])[0],
            'realmType' => $data->realmType,
            'password' => $payload['password'],
            'enabled' => $payload['enable'],
            'expiresAt' => $data->expiresAt,
        ]);
    }

    public function deleteUser(string $id, RealmType $realmType)
    {
        $response = $this->getHttpClient()
            ->withUrlParameters([
                'user' => $id.'@'.$realmType->value,
            ])
            ->delete('/api2/json/access/users/{user}')
            ->json();

        return $this->getData($response);
    }

    public function createRole(string $name, string $privileges)
    {
        $payload = [
            'roleid' => $name,
            'privs' => $privileges,
        ];

        $response = $this->getHttpClient()
            ->post('/api2/json/access/roles', $payload)
            ->json();

        return $this->getData($response);
    }

    public function createUserCredentials(RealmType $realmType, string $userid, string $password): UserCredentialsData
    {
        $response = $this->getHttpClient(shouldAuthorize: false)
            ->post('/api2/json/access/ticket', [
                'username' => $userid,
                'password' => $password,
                'realm' => $realmType->value,
            ])
            ->json();

        return UserCredentialsData::fromRaw($this->getData($response));
    }
}
