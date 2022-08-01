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

class UserController extends ApplicationApiController
{
    public function index(Request $request)
    {
        $users = QueryBuilder::for(User::query())
            ->allowedFilters(['name', 'email', 'root_admin'])
            ->allowedSorts(['id'])
            ->paginate($request->query('per_page') ?? 50);

        return $users;
    }

    public function show(User $user)
    {
        return new Response([
            'data' => $user
        ]);
    }


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

    public function destroy(User $user)
    {
        $user->delete();

        return $this->returnContent([
            'message' => 'User deleted'
        ]);
    }

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
