<?php

namespace Convoy\Http\Requests\Client\Servers;

use Convoy\Enums\Server\ConsoleType;
use Convoy\Http\Requests\FormRequest;
use Convoy\Models\Server;
use Illuminate\Validation\Rules\Enum;

class CreateConsoleSessionRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $server = $this->parameter('server', Server::class);

        return [
            'type' => [$server->node->coterm_enabled ? 'required' : 'exclude', new Enum(ConsoleType::class)]
        ];
    }
}
