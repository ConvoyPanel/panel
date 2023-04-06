<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Enums\Server\AuthenticationType;
use Convoy\Rules\EnglishKeyboardCharacters;
use Convoy\Rules\Password;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;
use phpseclib3\Crypt\PublicKeyLoader;

class UpdateSecurityRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'type' => [new Enum(AuthenticationType::class), 'required'],
            'ssh_keys' => ['nullable', 'string', 'exclude_unless:type,sshkeys'],
            'password' => ['string', 'min:8', 'max:191', new Password(), new EnglishKeyboardCharacters(), 'exclude_unless:type,cipassword'],
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
