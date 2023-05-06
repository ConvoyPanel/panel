<?php

namespace Convoy\Http\Controllers\Coterm;

use Convoy\Http\Controllers\Controller;
use Convoy\Models\Server;
use Convoy\Services\Servers\ServerConsoleService;
use Convoy\Transformers\Coterm\NoVncCredentialsTransformer;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(private ServerConsoleService $consoleService) {}

    public function store(Server $server)
    {
        $credentials = $this->consoleService->createNoVncCredentials($server);

        return fractal()->item([
            'server' => $server,
            'credentials' => $credentials,
        ], new NoVncCredentialsTransformer())->respond();
    }
}
