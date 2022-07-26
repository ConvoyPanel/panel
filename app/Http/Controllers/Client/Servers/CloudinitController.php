<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Models\Server;
use App\Services\Servers\CloudinitService;
use App\Http\Requests\Client\Servers\Security\UpdatePasswordRequest;
use App\Http\Requests\Client\Servers\Settings\UpdateNetworkConfigRequest;
use App\Enums\Servers\Cloudinit\AuthenticationType;
use App\Http\Requests\Client\Servers\Settings\UpdateBiosTypeRequest;
use App\Enums\Servers\Cloudinit\BiosType;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Yaml\Yaml;

class CloudinitController extends ApplicationApiController
{
    public function __construct(private CloudinitService $cloudinitService)
    {
    }

    public function fetchConfig(Server $server)
    {
        return $this->returnContent($this->cloudinitService->setServer($server)->fetchConfig());
    }

    public function updatePassword(Server $server, UpdatePasswordRequest $request)
    {
        $response = $this->cloudinitService->setServer($server)->changePassword($request->password, AuthenticationType::from($request->type));

        if ($response === null) {
            if (AuthenticationType::from($request->type) === AuthenticationType::KEY) {
                throw ValidationException::withMessages([
                    'password' => 'The public key is invalid.'
                ]);
            } else {
                throw ValidationException::withMessages([
                    'password' => 'The password was rejected by the server for an unknown reason.'
                ]);
            }
        }

        return $this->returnInertiaResponse($request, 'password-updated');
    }

    public function dumpConfig(Server $server)
    {
        $config = $this->cloudinitService->setServer($server)->dumpConfig();


        return Yaml::parse($config);
    }

    public function updateBios(Server $server, UpdateBiosTypeRequest $request)
    {
        $this->cloudinitService->setServer($server)->changeBIOS(BiosType::from($request->type));

        return $this->returnInertiaResponse($request, 'bios-updated');
    }

    public function updateNetworkConfig(Server $server, UpdateNetworkConfigRequest $request)
    {
        if ($request->hostname !== null) {
            $this->cloudinitService->setServer($server)->changeHostname($request->hostname);
        }

        $this->cloudinitService->setServer($server)->changeNameserver(implode(',', $request->nameservers));

        return $this->returnInertiaResponse($request, 'network-config-updated');
    }
}
