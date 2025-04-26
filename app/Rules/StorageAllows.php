<?php

namespace App\Rules;

use App\Enums\Node\Storage\StorageContentType;
use App\Models\Storage;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

// Keep Str for headline generation

class StorageAllows implements ValidationRule
{
    /**
     * The list of required storage content types (as Enum cases).
     *
     * @var StorageContentType[]
     */
    protected array $requiredContentTypes;

    /**
     * Create a new rule instance.
     *
     * @param  StorageContentType  ...$contentTypes  One or more content type Enum cases to check.
     */
    public function __construct(StorageContentType ...$contentTypes)
    {
        $this->requiredContentTypes = $contentTypes;
    }

    /**
     * Run the validation rule.
     *
     * Checks if the Storage model corresponding to the given ID ($value)
     * is configured to store all the specified content types (set to true).
     *
     * @param  string  $attribute  The name of the attribute being validated.
     * @param  mixed  $value  The value of the attribute (the storage ID).
     * @param  Closure(string): PotentiallyTranslatedString  $fail  The callback to call if validation fails.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $storage = Storage::find($value);

        if (! $storage) {
            $fail("The selected storage for {$attribute} is invalid.");

            return;
        }

        foreach ($this->requiredContentTypes as $contentTypeEnum) {
            // Get the corresponding model attribute name (e.g., 'stores_kvm') from the Enum.
            $attributeName = $contentTypeEnum->toModelAttributeName();

            // Check if the corresponding attribute exists on the model and if it's false.
            if (! isset($storage->{$attributeName}) || ! $storage->{$attributeName}) {
                // Generate a user-friendly name using the Enum case name (e.g., 'KVM').
                $friendlyContentTypeName = Str::headline($contentTypeEnum->name);

                $fail("The storage selected for {$attribute} cannot store: {$friendlyContentTypeName}.");

                return; // No need to check further content types for this storage ID
            }
        }
    }
}
