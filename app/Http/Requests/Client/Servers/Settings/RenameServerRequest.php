<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Models\Server;
use Convoy\Rules\Hostname;
use Illuminate\Foundation\Http\FormRequest;

class RenameServerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => Server::getRules()['name'],
            'hostname' => [...Server::getRules()['hostname'], ...[new Hostname]],
        ];
    }
}
