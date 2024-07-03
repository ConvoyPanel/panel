<?php

namespace Convoy\Http\Controllers\Coterm;

use Convoy\Enums\Server\ConsoleType;
use Convoy\Http\Controllers\Controller;
use Convoy\Http\Requests\Coterm\StoreSessionRequest;
use Convoy\Models\Server;
use Convoy\Services\Servers\ServerConsoleService;
use Convoy\Transformers\Coterm\NoVncCredentialsTransformer;
use Convoy\Transformers\Coterm\XTermCredentialsTransformer;

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
