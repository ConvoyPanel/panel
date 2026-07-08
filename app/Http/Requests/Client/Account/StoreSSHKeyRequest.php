<?php

namespace App\Http\Requests\Client\Account;

use App\Rules\SshPublicKey;
use Illuminate\Foundation\Http\FormRequest;

class StoreSSHKeyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:40',
            'public_key' => ['required', 'string', 'max:500', new SshPublicKey],
        ];
    }
}
