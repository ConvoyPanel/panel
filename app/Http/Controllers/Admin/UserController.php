<?php

namespace App\Http\Controllers\Admin;

use App\Data\Auth\SSOTokenData;
use App\Data\PaginationMeta;
use App\Data\User\UserData;
use App\Data\User\UserInviteData;
use App\Enums\Audit\AuditEvent;
use App\Enums\User\UserType;
use App\Facades\Audit;
use App\Http\Requests\Admin\Users\StoreUserRequest;
use App\Http\Requests\Admin\Users\UpdateUserRequest;
use App\Models\Filters\FiltersUserWildcard;
use App\Models\User;
use App\Notifications\UserInvited;
use App\Services\Mail\MailConfigurator;
use App\Services\Users\UserDeletionService;
use App\Services\Users\UserInviteService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserController
{
    public function __construct(
        private UserDeletionService $userDeletion,
        private UserInviteService $invites,
        private MailConfigurator $mail,
    ) {}

    public function index(Request $request)
    {
        $users = QueryBuilder::for(User::query())
            ->with('adminRole')
            ->withCount(['servers'])
            ->allowedFilters(
                [
                    AllowedFilter::exact('id'),
                    'name',
                    AllowedFilter::exact('email'),
                    // Guests are provisioned by a customer sharing a server rather than by the
                    // operator, and conflating the two is exactly what this filter exists to
                    // prevent. `admin_role_id` filters staff the same way.
                    AllowedFilter::exact('type'),
                    AllowedFilter::exact('adminRoleId', 'admin_role_id'),
                    AllowedFilter::custom('*', new FiltersUserWildcard),
                ],
            )
            // The admin list is sortable by every column it shows. Sorts are named after the
            // response's camelCase properties, since the table sends the column it sorted by.
            ->allowedSorts([
                'id',
                'name',
                'email',
                // `root_admin` is no longer a column, and a query builder cannot see the
                // accessor that replaced it. Sorting by the role id groups the unassigned
                // accounts together and the staff by role, which is what the column shows.
                AllowedSort::field('rootAdmin', 'admin_role_id'),
                AllowedSort::field('adminRoleId', 'admin_role_id'),
                'type',
                AllowedSort::field('serversCount', 'servers_count'),
                AllowedSort::field('createdAt', 'created_at'),
            ])
            ->defaultSort('name')
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return PaginationMeta::paginate($users, UserData::class);
    }

    /**
     * The whole account, for the detail page: counts, credential inventory, last sign-in and the
     * resources it holds across the fleet. The list endpoint stays lean — none of this is worth
     * computing fifty times a page.
     */
    public function show(User $user)
    {
        return UserData::detail($user);
    }

    public function store(StoreUserRequest $request)
    {
        $password = $request->input('password');
        $invited = $password === null || $password === '';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            // An invited account still needs a value in a NOT NULL column, so it gets 64 random
            // characters nobody has ever seen. It is not a password anyone can use — the account
            // is unreachable until the invite is redeemed, which is the intended state.
            'password' => $invited ? Str::random(64) : $password,
            'type' => $request->enum('type', UserType::class) ?? UserType::STANDARD,
            'admin_role_id' => $request->resolvedAdminRoleId(),
        ])->loadCount(['servers']);

        Audit::record(
            AuditEvent::ADMIN_USER_CREATED,
            subject: $user,
            properties: [
                'email' => $user->email,
                'type' => $user->type->value,
                'admin_role' => $user->adminRole?->name,
                'invited' => $invited,
            ],
        );

        $data = UserData::from($user);

        return $invited
            ? ['data' => $data, 'invite' => $this->sendInvite($user)]
            : $data;
    }

    /**
     * Issue a fresh invite for an account that has one outstanding, or never had one.
     *
     * Separate from `store` because the reasons to reach for it come later: the link expired,
     * it went to a spam folder, or mail was not configured when the account was made and now is.
     */
    public function invite(User $user)
    {
        return ['data' => $this->sendInvite($user)];
    }

    public function revokeInvite(User $user)
    {
        $this->invites->revoke($user);

        Audit::record(AuditEvent::ADMIN_USER_INVITE_REVOKED, subject: $user);

        return response()->noContent();
    }

    /**
     * Mint a link, email it when there is a relay to email it with, and hand it back either way.
     *
     * The link is returned even on success, because mail is not proof of delivery and plenty of
     * installs have no SMTP at all. An admin who can copy the link is never blocked by a mail
     * configuration — which is what keeps this flow strictly better than emailing a password.
     */
    private function sendInvite(User $user): UserInviteData
    {
        $token = $this->invites->issue($user);
        $link = UserInviteService::url($token);
        $ttl = (int) config('invites.ttl_days');

        $emailed = $this->mail->isConfigured();

        if ($emailed) {
            $user->notify(new UserInvited($link, $ttl));
        }

        // The link itself is never recorded: it is a working credential until it is redeemed.
        Audit::record(
            AuditEvent::ADMIN_USER_INVITED,
            subject: $user,
            properties: ['emailed' => $emailed],
        );

        return new UserInviteData(
            link: $link,
            expiresAt: CarbonImmutable::now()->addDays($ttl),
            emailed: $emailed,
        );
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $roleId = $request->resolvedAdminRoleId($user);
        $type = $request->enum('type', UserType::class) ?? $user->type;

        // Changing your own role is a one-way door: the screen you would fix it from is the one
        // you just narrowed. Another admin can still do it, which is the point.
        if ($user->is($request->user()) && $roleId !== $user->admin_role_id) {
            throw new BadRequestHttpException(
                'You cannot change the role on the account you are signed in as.',
            );
        }

        // A guest exists only to hold shares. The database refuses the combination too; this is
        // what turns that into a message rather than a 500.
        if ($type === UserType::GUEST && $roleId !== null) {
            throw new BadRequestHttpException('A guest account cannot hold an admin role.');
        }

        if ($type === UserType::GUEST && $user->type !== UserType::GUEST) {
            $user->loadCount('servers');

            if ($user->servers_count > 0) {
                throw new BadRequestHttpException(
                    'This account owns servers, so it cannot be turned into a guest.',
                );
            }
        }

        DB::transaction(function () use ($request, $user, $roleId, $type) {
            $previousRole = $user->adminRole?->name;
            $previousType = $user->type;
            $roleChanged = $roleId !== $user->admin_role_id;

            // Their API tokens were minted while they held the old role, and Sanctum abilities
            // are checked against the token rather than re-derived from the account. Revoking
            // them is what keeps a narrowed role from lingering on a credential.
            if ($roleChanged && $user->admin_role_id !== null) {
                $user->tokens()->delete();
            }

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'type' => $type,
                'admin_role_id' => $roleId,
                ...(is_null($request->password) ? [] : ['password' => $request->password]),
            ]);

            $user->unsetRelation('adminRole');

            // Which fields moved, never their values — this covers a password reset performed on
            // someone else's account, which is exactly the kind of thing the log exists for.
            Audit::record(
                AuditEvent::ADMIN_USER_UPDATED,
                subject: $user,
                properties: array_filter([
                    'email' => $user->wasChanged('email') ? $user->email : null,
                    'name' => $user->wasChanged('name') ? $user->name : null,
                    'password_changed' => $user->wasChanged('password') ?: null,
                ], fn ($value) => $value !== null),
            );

            // Separate, permanently retained events: who can administer the panel and which
            // accounts are outsiders are the two questions a privilege investigation starts from,
            // and neither should be buried in a generic "user updated" row.
            if ($roleChanged) {
                Audit::record(
                    AuditEvent::ADMIN_USER_ROLE_CHANGED,
                    subject: $user,
                    properties: ['from' => $previousRole, 'to' => $user->adminRole?->name],
                );
            }

            if ($previousType !== $user->type) {
                Audit::record(
                    AuditEvent::ADMIN_USER_TYPE_CHANGED,
                    subject: $user,
                    properties: ['from' => $previousType->value, 'to' => $user->type->value],
                );
            }
        });

        $user->loadCount(['servers']);

        return UserData::from($user);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            throw new BadRequestHttpException(
                'You cannot delete the account you are signed in as.',
            );
        }

        $user->loadCount('servers');

        if ($user->servers_count > 0) {
            throw new BadRequestHttpException(
                'The user cannot be deleted with servers still associated.',
            );
        }

        // Captured before the delete, and recorded after it succeeds: the subject morph will not
        // resolve once the row is gone, so these properties and actor_label are the whole record.
        $properties = ['name' => $user->name, 'email' => $user->email];

        $this->userDeletion->delete($user);

        Audit::record(AuditEvent::ADMIN_USER_DELETED, subject: $user, properties: $properties);

        return response()->noContent();
    }

    public function getSSOToken(User $user)
    {
        // A guest is not a customer of the provider, so there is no billing session for this link
        // to land in. Minting one would put an operator inside an account that exists only because
        // somebody else shared a server.
        if ($user->isGuest()) {
            throw new BadRequestHttpException('A guest account cannot be signed in to.');
        }

        // A single-use, expiring Laravel signed URL — the integration redirects the browser
        // straight to it. The `nonce` is consumed on first use (see Auth\SsoController) so a
        // captured link cannot be replayed within its short lifetime.
        $link = URL::temporarySignedRoute(
            'auth.sso.consume',
            CarbonImmutable::now()->addSeconds(config('sso.link_ttl')),
            ['uuid' => $user->uuid, 'nonce' => Str::random(40)],
        );

        // Admin-only in the catalog: this mints a link that logs the admin in as the user, and
        // the fact of it should not surface in that user's own feed. The link itself is never
        // recorded — it is a working credential until it is consumed.
        Audit::record(AuditEvent::ADMIN_USER_SSO_TOKEN_GENERATED, subject: $user);

        return new SSOTokenData(
            userId: $user->id,
            link: $link,
        );
    }
}
