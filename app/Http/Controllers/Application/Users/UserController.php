<?php

namespace Convoy\Http\Controllers\Application\Users;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Application\Users\StoreUserRequest;
use Convoy\Http\Requests\Application\Users\UpdateUserRequest;
use Convoy\Models\User;
use Convoy\Transformers\Application\UserTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends ApplicationApiController
{
    /**
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $users = QueryBuilder::for(User::query())
            ->allowedFilters(['name', 'email', 'root_admin'])
            ->allowedSorts(['id'])
            ->paginate($request->query('per_page') ?? 50);

        return fractal($users, new UserTransformer())->respond();
    }

    /**
     * @param  User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        return fractal($user, new UserTransformer())->respond();
    }

    /**
     * @param  StoreUserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'root_admin' => $request->root_admin,
        ]);

        return fractal($user, new UserTransformer())->respond();
    }

    /**
     * @param  User  $user
     * @return Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        return $this->returnNoContent();
    }

    /**
     * @param  User  $user
     * @param  UpdateUserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(User $user, UpdateUserRequest $request)
    {
        if (isset($request->password)) {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'root_admin' => $request->root_admin,
            ]);
        } else {
            $user->update($request->safe()->except(['password']));
        }

        return fractal($user, new UserTransformer())->respond();
    }
}
