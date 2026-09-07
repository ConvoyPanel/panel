<?php

namespace App\Http\Requests\Client;

use App\Services\Users\AccountPolicyResolver;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Removal is the same capability as upload, not a lesser one: with the switch off the stored
 * picture is what the operator's upstream put there, and clearing it is as much a change to what
 * the account shows as replacing it.
 *
 * A request with no rules rather than an `abort_unless` in the controller, so every avatar write
 * is gated in the same place as its siblings.
 */
class DeleteAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Resolved rather than injected: kept in step with its siblings, one of which extends a base class that fixes this signature.
        return app(AccountPolicyResolver::class)->for($this->user())->canChangeAvatar;
    }

    public function rules(): array
    {
        return [];
    }
}
