<?php

namespace App\Http\Controllers\Application\Users;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\Users\StoreUserRequest;
use App\Http\Requests\Application\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\QueryBuilder;

/**
 *
 */
class UserController extends ApplicationApiController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $users = QueryBuilder::for(User::query())
            ->allowedFilters(['name', 'email', 'root_admin'])
            ->allowedSorts(['id'])
            ->paginate($request->query('per_page') ?? 50);

        return $users;
    }

    /**
     * @param User $user
     * @return Response
     */
    public function show(User $user)
    {
        return new Response([
            'data' => $user
        ]);
    }


    /**
     * @param StoreUserRequest $request
     * @return Response
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'root_admin' => $request->root_admin
        ]);

        return $this->returnContent([
            'data' => $user,
            'message' => 'User created',
        ]);
    }

    /**
     * @param User $user
     * @return Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        return $this->returnContent([
            'message' => 'User deleted'
        ]);
    }

    /**
     * @param User $user
     * @param UpdateUserRequest $request
     * @return Response
     */
    public function update(User $user, UpdateUserRequest $request)
    {
        if (isset($request->password)) {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'root_admin' => $request->root_admin
            ]);
        } else {
            $user->update($request->safe()->except(['password']));
        }

        return $this->returnContent([
            'data' => $user,
            'message' => 'User updated',
        ]);
    }
}
