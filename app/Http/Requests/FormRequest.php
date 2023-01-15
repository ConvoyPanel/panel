<?php

namespace Convoy\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Webmozart\Assert\Assert;

abstract class FormRequest extends BaseFormRequest
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
//    public function authorize()
//    {
//        if (is_null($this->user())) {
//            return false;
//        }
//
//        return (bool) $this->user()->root_admin;
//    }

    public function requiredToOptional(array $rules): array
    {
        foreach ($rules as &$rule) {
            if (is_string($rule)) {
                $rule = str_replace('required', 'sometimes', $rule);
            }

            if (is_array($rule)) {
                $rule = $this->requiredToOptional($rule);
            }
        }

        return $rules;
    }

    /**
     * Returns the named route parameter and asserts that it is a real model that
     * exists in the database.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<T>  $expect
     * @return T
     * @noinspection PhpUndefinedClassInspection
     * @noinspection PhpDocSignatureInspection
     */
    public function parameter(string $key, string $expect)
    {
        $value = $this->route()->parameter($key);

        Assert::isInstanceOf($value, $expect);
        Assert::isInstanceOf($value, Model::class);
        Assert::true($value->exists);

        /* @var T $value */
        return $value;
    }
}
