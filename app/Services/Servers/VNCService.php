<?php

namespace App\Services\Servers;

use App\Services\ProxmoxService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class VNCService extends ProxmoxService
{
    public function createRole()
    {
        $vnc_role = [
            "roleid" => "VNC",
            "privs" => "VM.Console"
        ];
        return $this->proxmox()->access()->roles()->post($vnc_role);
    }

    public function createUser()
    {
        $user = [
            "enable" => "1",
            "userid" => "test",
            "email" => "",
            "password" => ""
        ];
        return $this->proxmox()->access()->users()->post($user);
    }

    public function getSessionCredentials()
    {
        return $this->instance()->vncproxy()->post();
    }

    public function getSessionEndpoint()
    {

        return $this->instance()->getPve()->getApiURL() . $this->instance()->vncwebsocket()->getWebsocketEndpoint();
    }

    public function temp()
    {
        $expirationDate = Carbon::now();
        $expirationDate->addDay();

        $user = [
            "enable" => '1',
            // IF YOU EDIT THIS BELOW. WATCH OUT! PROXMOX HAS A USERID LENGTH LIMIT. TEST BEFORE YOU COMMIT!!!
            "userid" => 'convoy-' . Str::random(50) . '@pam',
            // below fields doesn't work because it tries to change the password of an existing user
            //"email" => "",
            //"password" => Str::random(12),
            'expire' => $expirationDate->timestamp,
        ];

        $role = [
            "roleid" => "convoy-vnc",
            "privs" => "VM.Console"
        ];

        $this->mainInstance()->access()->users()->post($user);

        $this->mainInstance()->access()->roles()->post($role);

        $this->mainInstance()->access()->acl()->put([
            'path' => '/vms/' . '100',
            'users' => $user['userid'],
            'roles' => $role['roleid']
        ]);

        return $this->mainInstance()->access()->users()->userId($user['userid'])->token()->tokenId('convoy-vnc')->post(['userid' => $user['userid']]);
    }
}
