<?php

namespace App\Http\Requests\Admin\Servers\Disks;

use App\Enums\Node\Storage\StorageContentType;
use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use App\Models\Storage;
use App\Rules\StorageAllows;
use App\Services\Nodes\LiveStorageService;
use Closure;

class AddServerDiskRequest extends BaseApiRequest
{
    public function rules(): array
    {
        /** @var Server $server */
        $server = $this->parameter('server', Server::class);

        return [
            'storage_id' => [
                'required',
                'integer',
                'exists:storages,id',
                new StorageAllows(StorageContentType::KVM),
            ],
            'size' => [
                'required',
                'numeric',
                'min:1',
                // Reject a disk that won't fit the target storage's free-for-Convoy
                // (live physical free − reserve). Fails open when the node is offline.
                function (string $attribute, mixed $value, Closure $fail) use ($server) {
                    $storage = Storage::find($this->input('storage_id'));
                    if (! $storage instanceof Storage) {
                        return;
                    }

                    $free = app(LiveStorageService::class)->freeForConvoy($server->node, $storage);
                    if ($free !== null && (int) $value > $free) {
                        $fail("The storage \"{$storage->name}\" does not have enough disk space available.");
                    }
                },
            ],
        ];
    }
}
