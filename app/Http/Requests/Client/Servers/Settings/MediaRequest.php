<?php

namespace App\Http\Requests\Client\Servers\Settings;

use App\Http\Requests\BaseApiRequest;
use App\Models\ISO;

/**
 * Shared authorization for the ISO mount/unmount endpoints.
 *
 * The library is panel-wide now, so there is no longer a node to scope against
 * — every ISO is offerable on every node. What remains, and still matters, is
 * visibility: a hidden ISO is one an operator has deliberately kept out of the
 * customer-facing list, and without this check it is one guessed uuid away from
 * anyone. This is the same gate {@see SettingsController::getMedia()} lists by.
 */
class MediaRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        $iso = $this->parameter('iso', ISO::class);

        return ! $iso->hidden || $this->user()->root_admin;
    }

    public function rules(): array
    {
        return [];
    }
}
