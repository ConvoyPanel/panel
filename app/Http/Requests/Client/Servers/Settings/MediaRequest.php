<?php

namespace App\Http\Requests\Client\Servers\Settings;

use App\Http\Requests\BaseApiRequest;
use App\Models\ISO;
use App\Models\Server;

/**
 * Shared authorization for the ISO mount/unmount endpoints.
 *
 * Two gates, because they answer different questions. Whether this caller may change what is
 * mounted on this server is the sub-user permission; whether this particular ISO is one they are
 * allowed to see is visibility. The library is panel-wide now, so there is no longer a node to
 * scope against — a hidden ISO is one an operator deliberately kept out of the customer-facing
 * list, and without the second check it is one guessed uuid away from anyone. That is the same
 * gate {@see SettingsController::getMedia()} lists by.
 */
class MediaRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        $iso = $this->parameter('iso', ISO::class);

        if (! $this->user()->can('manageMedia', $this->parameter('server', Server::class))) {
            return false;
        }

        return ! $iso->hidden || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [];
    }
}
