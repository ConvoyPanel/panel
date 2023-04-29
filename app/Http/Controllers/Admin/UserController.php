<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Admin\Users\StoreUserRequest;
use Convoy\Http\Requests\Admin\Users\UpdateUserRequest;
use Convoy\Models\Filters\FiltersUser;
use Convoy\Models\SSOToken;
use Convoy\Models\User;
use Convoy\Transformers\Admin\UserTransformer;
use Convoy\Transformers\Application\SSOTokenTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserController extends ApplicationApiController
{
    public function index(Request $request)
    {
        $users = QueryBuilder::for(User::query())
            ->withCount(['servers'])
            ->allowedFilters([AllowedFilter::exact('id'), 'name', AllowedFilter::exact('email'), AllowedFilter::custom('*', new FiltersUser)])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($users, new UserTransformer())->respond();
    }

    public function show(User $user)
    {
        $user->loadCount(['servers']);

        return fractal($user, new UserTransformer())->respond();
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'root_admin' => $request->root_admin,
        ])->loadCount(['servers']);

        return fractal($user, new UserTransformer())->respond();
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'root_admin' => $request->root_admin,
            ...(is_null($request->password) ? [] : ['password' => Hash::make($request->password)]),
        ]);

        $user->loadCount(['servers']);

        return fractal($user, new UserTransformer())->respond();
    }

    public function destroy(User $user)
    {
        $user->loadCount('servers');

        if ($user->servers_count > 0) {
            throw new BadRequestHttpException('The user cannot be deleted with servers still associated.');
        }

        $user->tokens()->delete();

        $user->delete();

        return $this->returnNoContent();
    }

    public function getSSOToken(User $user)
    {
        $SSOToken = SSOToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', Str::random(50)),
        ]);

        return fractal($SSOToken, new SSOTokenTransformer)->respond();
    }
}
