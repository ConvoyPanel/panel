<?php

namespace App\Http\Requests\Admin\Webhooks;

use App\Http\Requests\BaseApiRequest;
use App\Support\Webhooks\WebhookEvents;
use Closure;

class WebhookRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $presence = $creating ? 'required' : 'sometimes';

        return [
            'url' => [$presence, 'string', 'max:2048', 'url:http,https'],
            'description' => ['sometimes', 'nullable', 'string', 'max:191'],
            'events' => [$presence, 'array', 'min:1', 'max:200'],
            'events.*' => [
                'required',
                'string',
                'distinct',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! is_string($value) || ! WebhookEvents::isValidSubscription($value)) {
                        $fail("{$value} is not an event a webhook can subscribe to.");
                    }
                },
            ],
            'enabled' => ['sometimes', 'boolean'],
            // Issues a new signing secret, returned once. The old one stops working at once.
            'rotate_secret' => ['sometimes', 'boolean'],
        ];
    }
}
