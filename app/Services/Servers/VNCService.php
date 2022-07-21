<?php

namespace App\Services\Servers;

use App\Services\ProxmoxService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Proxmox\PVE;

class VNCService extends ProxmoxService
{
    // deprecated
    public function getSessionCredentials()
    {
        return $this->instance()->vncproxy()->post();
    }

    public function getSessionEndpoint()
    {

        return $this->instance()->getPve()->getApiURL();
    }

    public function getTemporaryVncCredentials()
    {
        $expirationDate = Carbon::now();
        $expirationDate->addDay();

        $generatedUserId =  'convoy-' . Str::random(50);

        $user = [
            "enable" => '1',
            // IF YOU EDIT THIS BELOW. WATCH OUT! PROXMOX HAS A USERID LENGTH LIMIT. TEST BEFORE YOU COMMIT!!!
            "userid" => $generatedUserId . '@pve',
            // below fields doesn't work because it tries to change the password of an existing user
            //"email" => "",
            "password" => Str::random(60),
            'expire' => $expirationDate->timestamp,
        ];

        $role = [
            "roleid" => "convoy-vnc",
            "privs" => "VM.Console"
        ];

        $this->mainInstance()->access()->users()->post($user);

        $this->mainInstance()->access()->roles()->post($role);

        $this->mainInstance()->access()->acl()->put([
            'path' => '/vms/' . $this->server->vmid,
            'users' => $user['userid'],
            'roles' => $role['roleid']
        ]);

        // uses api token (doesn't work)
        //$token = $this->mainInstance()->access()->users()->userId($user['userid'])->token()->tokenId('convoy-vnc')->post(['userid' => $user['userid']]);
        $token = $this->mainInstance()->access()->ticket()->post(['username' => $user['userid'], 'password' => $user['password']])['data']['ticket'];

        return $token;
    }
}
