<?php

namespace App\Http\Controllers\Coterm;

use App\Enums\Server\ConsoleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coterm\StoreSessionRequest;
use App\Models\Server;
use App\Services\Servers\ServerConsoleService;
use App\Transformers\Coterm\NoVncCredentialsTransformer;
use App\Transformers\Coterm\XTermCredentialsTransformer;

class SessionController extends Controller
{
    public function __construct(private ServerConsoleService $consoleService)
    {
    }

    public function store(StoreSessionRequest $request, Server $server)
    {
        $consoleType = $request->enum('type', ConsoleType::class);

        if ($consoleType === ConsoleType::NOVNC) {
            $credentials = $this->consoleService->createNoVncCredentials($server);

            return fractal()->item([
                'server' => $server,
                'credentials' => $credentials,
            ], new NoVncCredentialsTransformer())->respond();
        } elseif ($consoleType === ConsoleType::XTERMJS) {
            $credentials = $this->consoleService->createXTermjsCredentials($server);

            return fractal()->item([
                'server' => $server,
                'credentials' => $credentials,
            ], new XTermCredentialsTransformer())->respond();
        }
    }
}
