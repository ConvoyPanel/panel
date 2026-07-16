<?php

namespace App\Traits;

use App\Exceptions\Proxmox\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HandlesProxmoxErrors
{
    /**
     * Determine if a "does not exist" error should be ignored.
     */
    protected function isNonexistentVMError(RequestException $e, string $message = 'does not exist'): bool
    {
        if (Str::contains(Arr::get($e->response->json(), 'message', ''), $message)) {
            return true;
        }

        return false;
    }
}
