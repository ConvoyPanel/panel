<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webmozart\Assert\Assert;

abstract class BaseApiRequest extends FormRequest
{
    /**
     * Tracks if the request has been validated internally or not to avoid
     * making duplicate validation calls.
     */
    private bool $hasValidated = false;

    public function authorize(): bool
    {
        return $this->user()->root_admin;
    }

    /**
     * Validate that the resource exists and can be accessed prior to booting
     * the validator and attempting to use the data.
     *
     * @throws AuthorizationException
     */
    protected function prepareForValidation(): void
    {
        if (! $this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        $this->hasValidated = true;
    }

    /*
     * Determine if the request passes the authorization check as well
     * as the exists check.
     *
     * @return bool
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function passesAuthorization(): bool
    {
        // If we have already validated we do not need to call this function
        // again. This is needed to work around Laravel's normal auth validation
        // that occurs after validating the request params since we are doing auth
        // validation in the prepareForValidation() function.
        if ($this->hasValidated) {
            return true;
        }

        if (! parent::passesAuthorization()) {
            return false;
        }

        // Only let the user know that a resource does not exist if they are
        // authenticated to access the endpoint. This avoids exposing that
        // an item exists (or does not exist) to the user until they can prove
        // that they have permission to know about it.
        if ($this->attributes->get('is_missing_model', false)) {
            throw new NotFoundHttpException(trans('exceptions.api.resource_not_found'));
        }

        return true;
    }

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
     * @template T of Model
     *
     * @param class-string<T> $expect
     * @return Model
     *
     * @noinspection PhpDocSignatureInspection
     */
    public function parameter(string $key, string $expect)
    {
        $value = $this->route()->parameter($key);

        Assert::isInstanceOf($value, $expect);
        Assert::isInstanceOf($value, Model::class);
        Assert::true($value->exists);

        /* @var Model $value */
        return $value;
    }
}
