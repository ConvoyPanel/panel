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
}