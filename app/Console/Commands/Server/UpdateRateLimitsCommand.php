<?php

namespace Convoy\Console\Commands\Server;

use Carbon\Carbon;
use Convoy\Models\Server;
use Convoy\Services\Servers\NetworkService;
use Illuminate\Console\Command;

class UpdateRateLimitsCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Update the network rate limits of all servers.';

    /**
     * @var string
     */
    protected $signature = 'c:server:update-rate-limits';

    /**
     * Handle command execution.
     */
    public function handle(NetworkService $service)
    {
        $servers = Server::all();

        foreach ($servers as $server) {
            echo "Updating id {$server->id}...    ";

            try {
                if ($server->bandwidth_usage >= $server->bandwidth_limit && isset($server->bandwidth_limit)) {
                    $service->updateRateLimit($server, 1);

                    echo 'LIMITED' . PHP_EOL;
                } else {
                    $service->setServer($server)->updateRateLimit($server);

                    echo 'UNLIMITED' . PHP_EOL;
                }
            } catch (\Exception $e) {
                echo 'FAILED' . PHP_EOL;
            }
        }
    }
}
