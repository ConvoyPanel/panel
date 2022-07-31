<?php

namespace App\Http\Controllers\Admin\Users\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\Settings\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(User $user)
    {
        return Inertia::render('admin/users/settings/Index', [
            'user' => $user,
        ]);
    }

    public function update(User $user, UpdateUserRequest $request)
    {
        if (isset($request->password))
        {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'root_admin' => $request->root_admin
            ]);
        } else {
            $user->update($request->safe()->except(['password']));
        }

        return redirect()->route('admin.users.show.settings', [$user->id]);
    }
}
