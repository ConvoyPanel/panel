<?php

namespace App\Http\Requests\Client\Servers\Settings;

use App\Enums\Server\ServerStatus;
use App\Http\Requests\BaseApiRequest;
use App\Rules\TemplateIsAvailable;
use App\Rules\TemplateFitsStorage;
use App\Models\Server;
use App\Models\Template;
use Illuminate\Validation\Validator;

class ReinstallServerRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $server = $this->parameter('server', Server::class);

        return $server->isReady() || $server->status === ServerStatus::DEFERRED_OS_SELECTION;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template_uuid' => [
                'required',
                'string',
                'exists:templates,uuid',
                new TemplateIsAvailable(),
                new TemplateFitsStorage(),
            ],
            'account_password' => ['required', 'string', 'min:8', 'max:191'],
            'start_on_completion' => 'present|boolean',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $server = $this->parameter('server', Server::class);

        $this->merge([
            'node_id' => $server->node_id,
            'limits' => [
                'disk' => $server->disk,
            ],
        ]);
    }

    /**
     * Get the validation hooks for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $template = Template::where('uuid', '=', $this->template_uuid)->first();

                if ($template && $template->is_admin_only && ! $this->user()->root_admin) {
                    $validator->errors()->add('template_uuid', 'You are not authorized to use this template.');
                }
            },
        ];
    }
}
