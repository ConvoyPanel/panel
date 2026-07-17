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
        return [
            'type' => ['required', new Enum(ConsoleType::class)],
        ];
    }
}
