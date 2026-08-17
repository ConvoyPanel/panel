<?php

namespace App\Http\Requests\Admin\Nodes\Storages;

use App\Http\Requests\BaseApiRequest;

/**
 * Points an already-registered storage at a second node.
 *
 * Only the id is validated here. Whether the two nodes are in the same PVE
 * cluster, and whether Proxmox actually reports the storage on this one, are
 * facts about the world rather than about the payload, so the controller answers
 * them where it can name the reason.
 */
class AttachStorageRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'storage_id' => [
                'required',
                'integer',
                'exists:storages,id',
            ],
        ];
    }
}
