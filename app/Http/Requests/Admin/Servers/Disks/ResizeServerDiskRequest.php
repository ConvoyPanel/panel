<?php

namespace App\Http\Requests\Admin\Servers\Disks;

use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use App\Models\ServerDisk;
use App\Services\Nodes\LiveStorageService;
use Closure;

class ResizeServerDiskRequest extends BaseApiRequest
{
    public function rules(): array
    {
        /** @var Server $server */
        $server = $this->parameter('server', Server::class);
        /** @var ServerDisk $disk */
        $disk = $this->parameter('disk', ServerDisk::class);

        return [
            'size' => [
                'required',
                'numeric',
                'min:1',
                // Only the *growth* consumes new space; live free already
                // reflects the disk's current allocation. Shrink is handled by
                // the service (CannotShrinkDiskException). Fails open offline.
                function (string $attribute, mixed $value, Closure $fail) use ($server, $disk) {
                    $delta = (int) $value - (int) $disk->size;
                    if ($delta <= 0) {
                        return;
                    }

                    $free = app(LiveStorageService::class)->freeForConvoy($server->node, $disk->storage);
                    if ($free !== null && $delta > $free) {
                        $fail("The storage \"{$disk->storage->name}\" does not have enough disk space available.");
                    }
                },
            ],
        ];
    }
}
