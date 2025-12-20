<?php

namespace App\Http\Requests\Client\Servers;

use App\Enums\Server\ServerStatus;
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

        return $server->status === ServerStatus::INSTALL_FAILED;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [];
    }
}
