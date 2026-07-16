<?php

namespace App\Jobs\Server;

use App\Models\AddressBlock;
use App\Models\Server;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Throwable;

class BatchSyncNetworkSettingsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        #[WithoutRelations]
        public AddressBlock $addressBlock
    ) {}

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        // Get all servers that have an IP address from this address block
        $servers = $this->addressBlock->addresses()
            ->whereNotNull('server_id')
            ->with('server')
            ->get()
            ->pluck('server')
            ->unique('id')
            ->filter(fn (Server $server) => $server->exists);

        // If no servers are found, exit early
        if ($servers->isEmpty()) {
            return;
        }

        // Create a batch of SyncNetworkSettingsJob for each server
        $jobs = $servers->map(fn (Server $server) => new SyncNetworkSettingsJob($server))->toArray();

        Bus::batch($jobs)
            ->name('Sync network settings for address block #'.$this->addressBlock->id)
            ->allowFailures()
            ->dispatch();
    }
}
