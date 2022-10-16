<?php

namespace Convoy\Console\Commands\Server;

use Carbon\Carbon;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxMetricsRepository;
use Illuminate\Console\Command;

class UpdateUsagesCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Update the bandwidth usage of all servers.';

    /**
     * @var string
     */
    protected $signature = 'c:server:update-usages';

    /**
     * Handle command execution.
     */
    public function handle(ProxmoxMetricsRepository $repository)
    {
        $servers = Server::all();

        foreach ($servers as $server) {
            echo "Updating id {$server->id}...    ";

            try {
                $metrics = $repository->setServer($server)->getMetrics('hour');

                foreach ($metrics as $metric) {
                    if (Carbon::createFromTimestamp($metric['time'])->gt(Carbon::parse($server->hydrated_at))) {

                        $server->update([
                            // multiply by 60 because the metrics are in bytes per second
                            'bandwidth_usage' => $server->bandwidth_usage + $metric['netin'] * 60 + $metric['netout'] * 60,
                            'hydrated_at' => Carbon::now(),
                        ]);
                    }
                }
                echo 'OK' . PHP_EOL;
            } catch (\Exception $e) {
                // Do nothing.
                echo 'FAILED' . PHP_EOL;
            }
        }
    }
}
