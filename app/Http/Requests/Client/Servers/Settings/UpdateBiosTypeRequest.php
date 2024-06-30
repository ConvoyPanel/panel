<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Enums\Server\BiosType;
use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Server;
use Illuminate\Validation\Rules\Enum;

class UpdateBiosTypeRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateBiosType', $this->parameter('server', Server::class));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => [new Enum(BiosType::class), 'alpha_dash', 'required'],
        ];
    }
}
