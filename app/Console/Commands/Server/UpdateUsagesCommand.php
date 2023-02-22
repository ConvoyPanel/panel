<?php

namespace Convoy\Console\Commands\Server;

use Carbon\Carbon;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxMetricsRepository;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Task;

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
            (new Task($this->output))->render("Server #{$server->id}", function () use ($repository, $server) {
                try {

                    $metrics = $repository->setServer($server)->getMetrics('hour');

                    $bandwidth = $server->bandwidth_usage;

                    foreach ($metrics as $metric) {
                        if (Carbon::createFromTimestamp($metric['time'])->gt(Carbon::parse($server->hydrated_at))) {
                            // we multiply it by 60 seconds because each metric is
                            // recorded every 1 minute but the values like netin and
                            // netout are in bytes/sec
                            $bandwidth += (int) $metric['netin'] * 60 + (int) $metric['netout'] * 60;
                        }
                    }

                    if ($bandwidth > 0) {
                        $server->update([
                            'bandwidth_usage' => $bandwidth,
                            'hydrated_at' => Carbon::now(),
                        ]);
                    }
                } catch (\Exception $e) {
                    // do nothing
                }
            });
        }
    }
}
