<?php

namespace App\Http\Controllers\Application\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Application\Users\StoreUserRequest;
use App\Http\Requests\Application\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return User::all();
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

        return new Response([
            'data' => $user,
            'message' => 'User created',
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

        return new Response([
            'data' => $user,
            'message' => 'User updated',
        ]);
    }


}
