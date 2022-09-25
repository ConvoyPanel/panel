<?php

namespace Convoy\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

abstract class AdminFormRequest extends FormRequest
{
    /**
     * The rules to apply to the incoming form request.
     *
     * @return array
     */
    abstract public function rules();

    /**
     * Determine if the user is an admin and has permission to access this
     * form controller in the first place.
     *
     * @return bool
     */
    public function authorize()
    {
        if (is_null($this->user())) {
            return false;
        }

        return (bool) $this->user()->root_admin;
    }

    public function convertRule(string $rule, array $rules, string $replaceWith)
    {
        return Arr::map($rules, function ($entry) use ($rule, $replaceWith) {
            if ($entry === $rule)
                return $replaceWith;

            return $entry;
        });
    }
}
