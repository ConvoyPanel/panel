<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use Convoy\Services\Servers\CloudinitService;
use Convoy\Http\Requests\Client\Servers\Security\UpdatePasswordRequest;
use Convoy\Http\Requests\Client\Servers\Settings\UpdateNetworkConfigRequest;
use Convoy\Enums\Servers\Cloudinit\AuthenticationType;
use Convoy\Http\Requests\Client\Servers\Settings\UpdateBiosTypeRequest;
use Convoy\Enums\Servers\Cloudinit\BiosType;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Yaml\Yaml;
use Exception;

class CloudinitController extends ApplicationApiController
{
    public function __construct(private CloudinitService $cloudinitService, private ProxmoxCloudinitRepository $repository)
    {
    }

    public function getConfig(Server $server)
    {
        return $this->repository->setServer($server)->getConfig();
    }

    public function updatePassword(Server $server, UpdatePasswordRequest $request)
    {
        try {
            if (AuthenticationType::from($request->type) === AuthenticationType::KEY)
            {
                $this->cloudinitService->setServer($server)->changePassword($request->contents, AuthenticationType::from($request->type));
            } else {
                $this->cloudinitService->setServer($server)->changePassword($request->password, AuthenticationType::from($request->type));
            }
        } catch (Exception $e) {
            if (AuthenticationType::from($request->type) === AuthenticationType::KEY) {
                throw ValidationException::withMessages([
                    'contents' => 'The public key is invalid.'
                ]);
            } else {
                throw ValidationException::withMessages([
                    'password' => 'The password was rejected by the server for an unknown reason.'
                ]);
            }
        }

        return back();
    }

    public function updateBios(Server $server, UpdateBiosTypeRequest $request)
    {
        $this->cloudinitService->setServer($server)->changeBIOS(BiosType::from($request->type));

        return back();
    }

    public function updateNetworkConfig(Server $server, UpdateNetworkConfigRequest $request)
    {
        if ($request->hostname !== null) {
            $this->cloudinitService->setServer($server)->changeHostname($request->hostname);
        }

        $this->cloudinitService->setServer($server)->changeNameserver(implode(',', $request->nameservers));

        return back();
    }
}
