<?php

namespace App\Http\Controllers\Client\Servers;

use App\Exceptions\Http\Server\Proxmox\GuestAgentUnavailableException;
use App\Models\Server;
use App\Services\Servers\ServerResourceService;
use Illuminate\Http\Request;

class ResourceController
{
    public function __construct(
        private ServerResourceService $resourceService
    ) {}

    public function __invoke(Request $request, Server $server)
    {
        $usage = $this->resourceService->getStorageUsage($server);

        return response()->json([
            'data' => $usage,
        ]);
    }
}
