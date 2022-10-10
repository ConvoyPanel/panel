<?php

namespace Convoy\Http\Controllers\Admin\Users;

use Convoy\Http\Controllers\Controller;
use Convoy\Http\Requests\Admin\Users\StoreUserRequest;
use Convoy\Models\User;
use Convoy\Transformers\Admin\UserTransformer as AdminUserTransformer;
use Convoy\Transformers\Application\UserTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('admin/users/Index', [
            'users' => fractal(User::paginate($request->query('per_page') ?? 50), new AdminUserTransformer())->toArray(),
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
            'root_admin' => $request->root_admin,
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
