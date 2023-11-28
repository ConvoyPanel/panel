<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Server;

class UpdateBootOrderRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateBootOrder', $this->parameter('server', Server::class));
    }

    public function rules(): array
    {
        return [
            'order' => 'array|present',
            'order.*' => 'string',
        ];
    }
}
