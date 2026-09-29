<?php

namespace App\Rules;

use App\Enums\User\UserType;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A guest account cannot own a server.
 *
 * A guest exists only because a customer shared one with them; giving it a server of its own
 * would make it a customer the provider never provisioned and has no record of, which is the
 * exact conflation guest accounts exist to avoid.
 */
class OwnerIsNotAGuest implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // `where` on the enum rather than reading the column back: the model casts `type`, so
        // a `value('type')` comparison against the string silently never matches.
        if (User::query()->whereKey($value)->where('type', '=', UserType::GUEST)->exists()) {
            $fail('A guest account cannot own a server.');
        }
    }
}
