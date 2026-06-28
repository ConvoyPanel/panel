<?php

namespace App\Http\Controllers\Coterm;

use App\Data\Server\ConsoleCredentialsData;
use App\Enums\Server\ConsoleType;
use App\Http\Requests\Coterm\StoreSessionRequest;
use App\Models\Server;
use App\Services\Servers\ServerConsoleService;

class SessionController
{
    public function __construct(private ServerConsoleService $consoleService) {}

    public function store(StoreSessionRequest $request, Server $server)
    {
        $consoleType = $request->enum('type', ConsoleType::class);

        if ($consoleType === ConsoleType::NOVNC) {
            $credentials = $this->consoleService->createNoVncCredentials($server);
        } elseif ($consoleType === ConsoleType::XTERMJS) {
            $credentials = $this->consoleService->createXTermjsCredentials($server);
        } else {
            return response()->json(['error' => 'Invalid console type'], 400);
        }

        return new ConsoleCredentialsData(
            nodeFqdn: $server->node->fqdn,
            nodePort: $server->node->port,
            nodePveName: $server->node->cluster,
            vmid: $server->vmid,
            credentials: $credentials->toArray(),
        );
    }
}
