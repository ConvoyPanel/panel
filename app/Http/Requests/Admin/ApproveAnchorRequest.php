<?php

namespace App\Http\Requests\Admin;

use App\Enums\Anchor\AnchorMode;
use App\Http\Requests\BaseApiRequest;
use App\Models\Anchor;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ApproveAnchorRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            /*
             * Required, and required *here* rather than at enrollment: it is
             * the one thing the machine cannot tell us. It knows what interface
             * it bound to; it cannot know how this panel routes back to it
             * through whatever NAT, tunnel or split-horizon DNS sits between.
             */
            'public_url' => ['required', 'url:http,https', 'max:2048'],
            'panel_url_override' => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            // The operator may rename a machine whose hostname means nothing to them.
            'name' => ['sometimes', 'string', 'max:191'],
            'relay_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('anchors', 'id')->where('mode', AnchorMode::RELAY->value),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var Anchor $anchor */
                $anchor = $this->parameter('anchor', Anchor::class);

                if ($anchor->isApproved()) {
                    $validator->errors()->add('anchor', 'This Anchor has already been approved.');
                }

                if ($anchor->enrolled_at === null) {
                    $validator->errors()->add(
                        'anchor',
                        'This Anchor has not enrolled yet, so there is nothing to approve.',
                    );
                }

                if ($anchor->mode === AnchorMode::RELAY && $this->filled('relay_id')) {
                    $validator->errors()->add('relay_id', 'A relay cannot route through another relay.');
                }
            },
        ];
    }
}
