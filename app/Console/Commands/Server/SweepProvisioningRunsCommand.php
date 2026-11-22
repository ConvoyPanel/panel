<?php

namespace App\Console\Commands\Server;

use App\Services\Provisioners\ProvisioningService;
use Illuminate\Console\Command;

/**
 * Stops waiting on provisioner scripts that never reported, and takes their
 * snippets off the nodes.
 */
class SweepProvisioningRunsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'servers:sweep-provisioning-runs';

    /**
     * @var string
     */
    protected $description = 'Times out provisioner runs past their deadline and deletes snippets nothing waits on.';

    public function handle(ProvisioningService $service): void
    {
        $service->sweep();
    }
}
