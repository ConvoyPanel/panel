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
use App\Services\Users\UserDeletionService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserController
{
    public function __construct(
        private JWTService $JWTService,
        private UserDeletionService $userDeletion,
    ) {}

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
            'password' => $request->password,
            'root_admin' => $request->root_admin,
        ])->loadCount(['servers']);

        return UserData::from($user);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        DB::transaction(function () use ($request, $user) {
            // Demoting an admin: revoke their API tokens so elevated access
            // doesn't linger on tokens issued while they were an admin.
            if ($user->root_admin && ! $request->boolean('root_admin')) {
                $user->tokens()->delete();
            }

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'root_admin' => $request->root_admin,
                ...(is_null($request->password) ? [] : ['password' => $request->password]),
            ]);
        });

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

        $this->userDeletion->delete($user);

        return response()->noContent();
    }

    public function getSSOToken(User $user)
    {
        $token = $this->JWTService->issue(
            signingKey: config('app.key'),
            audience: config('app.url'),
            identifier: $user->uuid,
            claims: ['user_uuid' => $user->uuid],
            expiresAt: CarbonImmutable::now()->addSeconds(15),
        );

        return new SSOTokenData(
            userId: $user->id,
            token: $token->toString(),
        );
    }
}
