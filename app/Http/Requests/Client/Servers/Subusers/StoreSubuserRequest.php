<?php

namespace App\Http\Requests\Client\Servers\Subusers;

use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use App\Models\ServerSubuser;

/**
 * Sharing is owner-only, and not delegable.
 *
 * A permission to grant permissions is a permission to grant every permission, so there is no
 * sub-user permission that opens this. An operator with `servers.manage` is admitted by the
 * policy's `before`, which is how support can unpick a share the owner has lost access to.
 */
class StoreSubuserRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageSubusers', $this->parameter('server', Server::class));
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'between:1,191'],
            ...ServerSubuser::permissionRules(),
        ];
    }
}
