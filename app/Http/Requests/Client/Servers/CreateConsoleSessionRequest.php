<?php

namespace App\Http\Requests\Client\Servers;

use App\Enums\Server\ConsoleType;
use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use Illuminate\Validation\Rules\Enum;

class CreateConsoleSessionRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            'createConsoleSession',
            $this->parameter('server', Server::class),
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
