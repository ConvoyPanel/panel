<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\Controller;
use Convoy\Http\Requests\Admin\Users\StoreUserRequest;
use Convoy\Models\Filters\FiltersUser;
use Convoy\Models\User;
use Convoy\Transformers\Admin\UserTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = QueryBuilder::for(User::query())
            ->withCount(['servers'])
            ->allowedFilters([AllowedFilter::exact('id'), 'name', AllowedFilter::exact('email'), AllowedFilter::custom('*', new FiltersUser)])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($users, new UserTransformer())->respond();
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
}
