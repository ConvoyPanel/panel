<?php

namespace App\Http\Requests\Client\Servers\Settings;

use App\Auth\IdentityConfirmation;
use App\Enums\Server\AuthenticationType;
use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use App\Rules\Password;
use App\Rules\USKeyboardCharacters;
use Exception;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;
use phpseclib3\Crypt\PublicKeyLoader;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UpdateAuthSettingsRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        // A root password is a standing credential for the guest: it is applied on the
        // next boot and then outlives this session, this panel account's password, and
        // any revoked access to the server. So it gets the same identity gate as the
        // account credentials that outlive their session (API tokens, SSH keys) rather
        // than trusting a live cookie alone. The gate is on the password branch only —
        // rewriting the authorized key set stays a plain permission check, as before.
        if ($this->input('type') === AuthenticationType::PASSWORD->value
            && ! IdentityConfirmation::isConfirmed($this->session())) {
            throw new AccessDeniedHttpException('Your identity must be confirmed to set a root password.');
        }

        return $this->user()->can('updateAuthSettings', $this->parameter('server', Server::class));
    }

    public function rules(): array
    {
        return [
            'type' => [new Enum(AuthenticationType::class), 'required'],
            'ssh_keys' => ['nullable', 'string', 'exclude_unless:type,ssh_keys'],
            'password' => ['string', 'min:8', 'max:191', new Password, new USKeyboardCharacters, 'exclude_unless:type,password'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $type = $this->request->get('type');
                $sshKeys = explode(PHP_EOL, $this->request->get('ssh_keys'));

                if ($type === AuthenticationType::KEY->value) {
                    try {
                        foreach ($sshKeys as $key) {
                            if (strlen($key) > 0) {
                                PublicKeyLoader::load($key);
                            }
                        }
                    } catch (Exception $e) {
                        $validator->errors()->add('ssh_keys', 'The SSH key(s) are invalid.');
                    }
                }
            },
        ];
    }
}
