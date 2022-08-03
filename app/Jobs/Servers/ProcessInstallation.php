<?php

namespace App\Jobs\Servers;

use App\Models\Server;
use App\Models\Template;
use App\Services\Servers\CloudinitService;
use App\Services\Servers\InstallService;
use App\Services\Servers\NetworkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInstallation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $templateId, protected int $serverId, protected string $target, protected int $vmid, protected array $addresses)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $server = Server::find($this->serverId);

        $server->update(['installing' => true]);

        (new InstallService)->setServer(Template::find($this->templateId)->server)->install($this->vmid, $this->target);

        if (isset($this->addresses))
        {
            $networkService = new NetworkService;
            $cloudinitService = new CloudinitService;

            $networkService->setServer($server);
            $cloudinitService->setServer($server);

            $networkService->clearIpSets(); // to prevent any bugs

            $parsedAddresses = [];

            foreach ($this->addresses as $address)
            {
                if (empty($address)) continue;
                $parsedAddresses[] = [
                    'cidr' => "{$address['address']}/{$address['cidr']}",
                    'gateway' => $address['gateway'],
                ];
            }

            $cloudinitService->updateIpConfig($parsedAddresses);

            $networkService->lockIps(array_column($this->addresses, 'address'));
        }

        $server->update(['installing' => false]);
    }
}
