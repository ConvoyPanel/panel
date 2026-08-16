<?php

namespace App\Http\Requests\Client\Servers\Settings;

use App\Auth\IdentityConfirmation;
use App\Enums\Server\ServerLifecycle;
use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use App\Models\Template;
use App\Rules\TemplateFitsStorage;
use App\Rules\TemplateIsAvailable;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ReinstallServerRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $server = $this->parameter('server', Server::class);

        // This route is exempt from AuthenticateServerAccess (a server awaiting OS selection
        // has to reach it), so the suspension check has to happen here or not at all.
        if ($server->isSuspended()) {
            return false;
        }

        // Rebuilding a live server destroys its disk, so it gets the same identity gate as
        // the other irreversible acts (setting a root password, minting a token) rather than
        // trusting a live cookie alone. The gate is on the rebuild branch only: a server in
        // DEFERRED_OS_SELECTION has nothing to erase, and gating its first install would
        // stop a brand-new account from reaching a usable server at all.
        if ($server->isReady() && ! IdentityConfirmation::isConfirmed($this->session())) {
            throw new AccessDeniedHttpException('Your identity must be confirmed to rebuild a server.');
        }

        return $server->isReady() || $server->lifecycle === ServerLifecycle::DEFERRED_OS_SELECTION;
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
                new TemplateIsAvailable,
                new TemplateFitsStorage,
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
