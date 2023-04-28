<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Http\Requests\FormRequest;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Rules\EnglishKeyboardCharacters;
use Convoy\Rules\Password;

class ReinstallServerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template_uuid' => 'required|string|exists:templates,uuid',
            'account_password' => ['required', 'string', 'min:8', 'max:191', new Password(), new EnglishKeyboardCharacters()],
            'start_on_completion' => 'present|boolean',
        ];
    }

    // check if the template belongs to the same node as the server
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $template = Template::where('uuid', '=', $this->template_uuid)->firstOrFail();
            $server = $this->parameter('server', Server::class);

            if ($server->node_id !== $template->group->node_id) {
                $validator->errors()->add('template_uuid', 'The selected template is invalid.');
            }
        });
    }
}
