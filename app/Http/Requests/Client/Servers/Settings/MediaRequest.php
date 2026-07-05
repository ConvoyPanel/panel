<?php

namespace App\Http\Requests\Client\Servers\Settings;

use App\Http\Requests\BaseApiRequest;
use App\Models\ISO;
use App\Models\Server;

/**
 * Shared authorization for the ISO mount/unmount endpoints.
 *
 * The {iso} route parameter is bound globally by id (both routes opt out of
 * scoped bindings, because ISOs relate to a node via storage, not directly to
 * the server), so we must confirm the ISO is actually one this server's node
 * exposes to the user — the exact gate {@see SettingsController::getMedia()}
 * uses to list them. Without this a customer could mount/unmount ISOs from
 * other nodes, or hidden ISOs, just by guessing a small integer id.
 */
class MediaRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        $server = $this->parameter('server', Server::class);
        $iso = $this->parameter('iso', ISO::class);

        return $server->node->isos()
            ->whereKey($iso->getKey())
            ->where('is_successful', true)
            ->when(! $this->user()->root_admin, fn ($query) => $query->where('hidden', false))
            ->exists();
    }

    public function rules(): array
    {
        return [];
    }
}
