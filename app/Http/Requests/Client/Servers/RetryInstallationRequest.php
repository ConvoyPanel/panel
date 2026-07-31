<?php

namespace App\Http\Requests\Client\Servers;

use App\Enums\Server\ServerLifecycle;
use App\Http\Requests\BaseApiRequest;
use App\Models\Server;

class RetryInstallationRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $server = $this->parameter('server', Server::class);

        // Exempt from AuthenticateServerAccess (a failed install is by definition not ready),
        // so suspension is only enforced here.
        return ! $server->isSuspended() && $server->lifecycle === ServerLifecycle::INSTALL_FAILED;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [];
    }
}
