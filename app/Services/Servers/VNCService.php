<?php

namespace App\Services\Servers;

use App\Services\ProxmoxService;

class VNCService extends ProxmoxService
{
    public function createRole()
    {
        $vnc_role = [
            "roleid" => "VNC",
            "privs" => [
                "VM.Console"
            ]
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
}