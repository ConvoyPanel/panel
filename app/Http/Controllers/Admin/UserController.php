<?php

namespace App\Http\Controllers\Admin;

use App\Data\Auth\SSOTokenData;
use App\Data\PaginationMeta;
use App\Data\User\UserData;
use App\Http\Requests\Admin\Users\StoreUserRequest;
use App\Http\Requests\Admin\Users\UpdateUserRequest;
use App\Models\Filters\FiltersUserWildcard;
use App\Models\User;
use App\Services\Api\JWTService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserController
{
    public function __construct(private JWTService $JWTService) {}

    public function index(Request $request)
    {
        $users = QueryBuilder::for(User::query())
            ->withCount(['servers'])
            ->allowedFilters(
                [AllowedFilter::exact('id'), 'name', AllowedFilter::exact(
                    'email',
                ), AllowedFilter::custom('*', new FiltersUserWildcard)],
            )
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return PaginationMeta::paginate($users, UserData::class);
    }

    public function show(User $user)
    {
        $user->loadCount(['servers']);

        return UserData::from($user);
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'root_admin' => $request->root_admin,
        ])->loadCount(['servers']);

        return UserData::from($user);
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

        return UserData::from($user);
    }

    public function destroy(User $user)
    {
        $user->loadCount('servers');

        if ($user->servers_count > 0) {
            throw new BadRequestHttpException(
                'The user cannot be deleted with servers still associated.',
            );
        }

        $user->tokens()->delete();

        $user->delete();

        return response()->noContent();
    }

    public function getSSOToken(User $user)
    {
        $token = $this->JWTService
            ->setExpiresAt(CarbonImmutable::now()->addSeconds(15))
            ->setUser($user)
            ->handle(config('app.key'), config('app.url'), $user->uuid);

        return new SSOTokenData(
            userId: $user->id,
            token: $token->toString(),
        );
    }
}
