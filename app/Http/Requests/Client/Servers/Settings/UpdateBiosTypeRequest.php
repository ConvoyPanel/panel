<?php

namespace App\Http\Requests\Client\Servers\Settings;

use App\Enums\Server\BiosType;
use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
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
