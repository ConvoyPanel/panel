<?php

namespace App\Support\Provisioners;

use App\Models\ImageDefinition;
use Closure;

/**
 * Validation for the values a server's provisioner fields are given.
 *
 * Shared by the admin create request and the client reinstall request, so a
 * value that one accepts the other accepts too.
 */
final class ProvisionerFieldRules
{
    /**
     * At most this many characters per value. A field is a domain, a
     * username, a licence key -- not a file.
     */
    public const MAX_LENGTH = 1000;

    /**
     * @return array<string, mixed>
     */
    public static function for(?ImageDefinition $image): array
    {
        $fields = $image?->provisionerFields() ?? collect();
        $keys = $fields->pluck('key')->all();

        $rules = [
            'provisioner_fields' => [
                'sometimes',
                'nullable',
                'array',
                function (string $attribute, mixed $value, Closure $fail) use ($keys) {
                    if (! is_array($value)) {
                        return;
                    }

                    $unknown = array_diff(array_map('strval', array_keys($value)), $keys);

                    if ($unknown !== []) {
                        $fail('This image has no field named '.implode(', ', $unknown).'.');
                    }
                },
            ],
        ];

        foreach ($fields as $field) {
            $rules["provisioner_fields.{$field->key}"] = [
                $field->required ? 'required' : 'nullable',
                'string',
                'max:'.self::MAX_LENGTH,
                // Values reach the script as environment variables, one per
                // line. A line break would start a variable of its own.
                'regex:/^[^\p{Cc}]*$/u',
            ];
        }

        return $rules;
    }

    /**
     * Error messages that name a field by its label rather than its key.
     *
     * @return array<string, string>
     */
    public static function attributes(?ImageDefinition $image): array
    {
        return ($image?->provisionerFields() ?? collect())
            ->mapWithKeys(fn ($field) => ["provisioner_fields.{$field->key}" => $field->label])
            ->all();
    }
}
