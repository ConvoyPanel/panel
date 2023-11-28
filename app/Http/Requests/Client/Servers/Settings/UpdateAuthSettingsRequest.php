<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Server;
use Exception;
use Convoy\Rules\Password;
use Faker\Provider\Base;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rules\Enum;
use phpseclib3\Crypt\PublicKeyLoader;
use Convoy\Enums\Server\AuthenticationType;
use Convoy\Rules\EnglishKeyboardCharacters;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthSettingsRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateAuthSettings', $this->parameter('server', Server::class));
    }

    public function rules(): array
    {
        return [
            'type' => [new Enum(AuthenticationType::class), 'required'],
            'ssh_keys' => ['nullable', 'string', 'exclude_unless:type,ssh_keys'],
            'password' => ['string', 'min:8', 'max:191', new Password(), new EnglishKeyboardCharacters(
            ), 'exclude_unless:type,password'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
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
        });
    }
}
