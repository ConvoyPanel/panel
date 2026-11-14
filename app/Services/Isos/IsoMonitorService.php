<?php

namespace Convoy\Services\Isos;

use Carbon\Carbon;
use Closure;
use Convoy\Models\ISO;
use Convoy\Repositories\Proxmox\Node\ProxmoxStorageRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxActivityRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class IsoMonitorService
{
    public function __construct(
        private ProxmoxActivityRepository $repository,
        private ProxmoxStorageRepository $storage,
    ) {
    }

    public function checkDownloadProgress(ISO $iso, string $upid, ?Closure $callback = null): void
    {
        $status = $this->repository->setNode($iso->node)->getStatus($upid);

        if (Arr::get($status, 'status') === 'running') {
            if ($callback) {
                $callback();
            }

            return;
        }

        if (Str::lower(Arr::get($status, 'exitstatus')) === 'ok') {
            $size = $this->downloadedSize($iso);

            $iso->update([
                'is_successful' => true,
                'completed_at' => Carbon::now(),
                ...($size !== null ? ['size' => $size] : []),
            ]);
        } else {
            $iso->update([
                'is_successful' => false,
                'completed_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * The size of the file the node actually saved. The size recorded when
     * the download started is the URL's metadata from before the download,
     * which can differ from the file -- the library showed "2 MiB" for an ISO
     * the server's drive listed as 2.95 MiB. Null if the node can't say, in
     * which case the recorded size stays.
     */
    private function downloadedSize(ISO $iso): ?int
    {
        try {
            return $this->storage->setNode($iso->node)->getIsos()
                ->where('file_name', '=', $iso->file_name)
                ->first()?->size;
        } catch (Throwable) {
            return null;
        }
    }
}
