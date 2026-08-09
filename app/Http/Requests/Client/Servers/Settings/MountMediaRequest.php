<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Models\ISO;
use Convoy\Http\Requests\BaseApiRequest;

class MountMediaRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        $iso = $this->parameter('iso', ISO::class);

        // Only the hidden flag is checked here. That the ISO belongs to the
        // server's node is settled before this runs, by the scoped route-model
        // binding resolving {iso} through Server::isos() — see RouteScopingTest.
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
