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

                $bandwidth = 0;

                foreach ($metrics as $metric) {
                    if (Carbon::createFromTimestamp($metric['time'])->gt(Carbon::parse($server->hydrated_at))) {
                        // we multiply it by 60 because each metric is for 1 minute but the values like netin and netout are in bytes/sec
                        $bandwidth += $server->bandwidth_usage + (int) $metric['netin'] * 60 + (int) $metric['netout'] * 60;
                    }
                }

                if ($bandwidth > 0) {
                    $server->update([
                        'bandwidth_usage' => $bandwidth,
                        'hydrated_at' => Carbon::now(),
                    ]);
                }

                echo 'OK' . PHP_EOL;
            } catch (\Exception $e) {
                // Do nothing.
                echo 'FAILED' . PHP_EOL;
            }
        }
    }
}
