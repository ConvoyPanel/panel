<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Models\ISO;
use Convoy\Http\Requests\BaseApiRequest;

class MountMediaRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        $iso = $this->parameter('iso', ISO::class);

        // check if they're authorized to mount a hidden media (iso)
        if ($iso->hidden && !$this->user()->root_admin) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
