<?php

namespace App\Http\Requests\Client\Servers\Subusers;

use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use App\Models\ServerSubuser;

class UpdateSubuserRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageSubusers', $this->parameter('server', Server::class));
    }

    public function rules(): array
    {
        return ServerSubuser::permissionRules();
    }
}
