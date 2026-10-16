<?php

namespace App\Http\Requests\Admin;

use App\Enums\Admin\AdminPermission;
use App\Http\Requests\BaseApiRequest;
use App\Models\AdminRole;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminRoleFormRequest extends BaseApiRequest
{
    /**
     * Only a superadmin edits the catalog of who may do what.
     *
     * `users.manage` is enough to assign an existing role, which is the everyday act. Authoring a
     * role is not: three of the permissions (`tokens.manage`, `settings.manage`,
     * `users.impersonate`) are each a path back to full control, so an operator who could mint a
     * role holding them could promote themselves in one step.
     */
    public function authorize(): bool
    {
        if (! $this->user()->root_admin) {
            throw new AccessDeniedHttpException('Only a full administrator can author roles.');
        }

        return true;
    }

    public function rules(): array
    {
        $role = $this->route('admin_role');

        $rules = $role instanceof AdminRole
            ? AdminRole::getRulesForUpdate($role)
            : AdminRole::getRules();

        return [
            'name' => $rules['name'],
            'description' => $rules['description'],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'in:'.implode(',', AdminPermission::values())],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $role = $this->route('admin_role');

                // A system role's wording is the operator's to change; what it grants is not.
                // Something has to stay recognisable when a custom role goes wrong.
                if ($role instanceof AdminRole
                    && $role->is_system
                    && $this->array('permissions') !== $role->permissions) {
                    $validator->errors()->add(
                        'permissions',
                        'A built-in role\'s permissions cannot be changed. Duplicate it instead.',
                    );
                }
            },
        ];
    }
}
