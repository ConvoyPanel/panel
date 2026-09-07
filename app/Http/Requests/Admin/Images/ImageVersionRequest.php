<?php

namespace App\Http\Requests\Admin\Images;

use App\Enums\Image\ImageDiskRole;
use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class ImageVersionRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'max:32', 'regex:/^\d+\.\d+\.\d+$/'],
            'is_active' => 'sometimes|boolean',

            'disks' => 'required|array|min:1',
            'disks.*.slot' => ['required', 'string', 'regex:/^(?:scsi|ide|sata|virtio|efidisk)\d+$/'],
            'disks.*.role' => ['required', Rule::in(ImageDiskRole::values())],

            // Exactly one source per disk: a link the operator hosts, or a file
            // they uploaded. Both are the same thing to a node, so allowing
            // both would only leave a question about which one won.
            'disks.*.url' => ['nullable', 'required_without:disks.*.path', 'prohibits:disks.*.path', 'url'],
            'disks.*.path' => ['nullable', 'required_without:disks.*.url', 'string'],

            'disks.*.sha256' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/i'],
            'disks.*.size' => 'required|integer|min:1',
            'disks.*.virtual_size' => 'required|integer|min:1',
            'disks.*.format' => 'sometimes|string|in:qcow2,raw',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $roles = collect($this->input('disks', []))->pluck('role');

                if ($roles->filter(fn ($role) => $role === ImageDiskRole::SYSTEM->value)->count() !== 1) {
                    $validator->errors()->add('disks', 'A version needs exactly one system disk.');
                }
            },
        ];
    }
}
