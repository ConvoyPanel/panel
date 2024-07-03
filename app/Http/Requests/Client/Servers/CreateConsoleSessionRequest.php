<?php

namespace Convoy\Http\Requests\Client\Servers;

use Convoy\Enums\Server\ConsoleType;
use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Server;
use Illuminate\Validation\Rules\Enum;

class CreateConsoleSessionRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            'createConsoleSession', $this->parameter('server', Server::class),
        );
    }

    public function rules(): array
    {
        $server = $this->parameter('server', Server::class);

        return [
            'type' => [$server->node->coterm_enabled ? 'required' : 'exclude', new Enum(
                ConsoleType::class,
            )],
        ];
    }
}
