<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\StoreUserRequest;
use App\Models\User;
use App\Transformers\Application\UserTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/users/Index', [
            'users' => User::all()
        ]);
    }

    public function show(User $user)
    {
        return Inertia::render('admin/users/Show', [
            'user' => $user,
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

        return redirect()->route('admin.users.show', [$user->id]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users');
    }

    public function search(Request $request)
    {
        $users = QueryBuilder::for(User::query())
            ->allowedFilters(['name', 'email', 'root_admin'])
            ->allowedSorts(['id'])
            ->paginate($request->query('per_page') ?? 50);

        return fractal($users, new UserTransformer())->respond();
    }
}
