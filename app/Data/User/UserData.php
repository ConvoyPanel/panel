<?php

namespace App\Data\User;

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapInputName(SnakeCaseMapper::class)]
class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $avatarUrl,
        public bool $rootAdmin,
        public int|Optional $serversCount,
        public ?CarbonImmutable $createdAt,
        /*
         * Everything below belongs to the admin detail page and is Optional on purpose. This same
         * object is the payload of `/api/client/user` and of `createdBy` on an API key, and those
         * responses have no business carrying an account's credential inventory — an Optional that
         * is never filled is omitted from the JSON entirely, so they stay exactly as they were.
         *
         * {@see self::detail()} is the only thing that fills them.
         */
        public int|Optional $apiKeysCount = new Optional,
        public int|Optional $sshKeysCount = new Optional,
        public int|Optional $passkeysCount = new Optional,
        public int|Optional $oauthConnectionsCount = new Optional,
        public bool|Optional $twoFactorEnabled = new Optional,
        public CarbonImmutable|Optional|null $lastLoginAt = new Optional,
        public string|Optional|null $lastLoginIp = new Optional,
        public UserResourcesData|Optional $resources = new Optional,
        /*
         * What this account may change about itself. Filled only by {@see self::forSelf()}, which
         * is what the endpoints returning the *signed-in* user use — an admin listing other people
         * has no use for their capabilities, and `createdBy` on an API key certainly does not.
         */
        public AccountCapabilitiesData|Optional $accountCapabilities = new Optional,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            avatarUrl: $user->avatarUrl(),
            rootAdmin: (bool) $user->root_admin,
            serversCount: isset($user->servers_count)
                ? (int) $user->servers_count
                : Optional::create(),
            createdAt: $user->created_at
                ? CarbonImmutable::parse($user->created_at)
                : null,
        );
    }

    /**
     * The signed-in account, as returned to itself: the base payload plus the policy the account
     * screen reads to decide which fields to offer.
     *
     * Takes the resolved capabilities rather than the resolver so this stays a plain mapper, and
     * so the caller is the one that decided whose policy this is.
     */
    public static function forSelf(User $user, AccountCapabilitiesData $capabilities): self
    {
        $base = self::fromModel($user);

        $base->accountCapabilities = $capabilities;

        return $base;
    }

    /**
     * The whole account, for the admin's user detail page: what it owns, and every credential that
     * can be used to sign in as it.
     *
     * Three queries — the counts, the resource aggregate, and the last login — rather than loading
     * four collections to call `count()` on each.
     */
    public static function detail(User $user): self
    {
        $user->loadCount([
            'servers',
            'sshKeys',
            'passkeys',
            'oauthConnections',
            // `apiKeys`, not `tokens`: the relation already excludes application tokens, which
            // belong to the panel rather than to the person and are invisible on this page.
            'apiKeys',
        ]);

        /*
         * Derived from the audit log rather than a column on `users`. The panel already records
         * every successful sign-in with its IP and keeps those rows forever
         * ({@see AuditEvent::retention()}), so a column would be a second, weaker copy of a fact
         * already stored — weaker because it cannot say where the login came from without a second
         * column, and because nothing would backfill it for accounts that signed in before it
         * existed.
         */
        $lastLogin = AuditLog::query()
            ->where('event', '=', AuditEvent::AUTH_LOGIN_SUCCEEDED)
            ->whereMorphedTo('actor', $user)
            ->latest('id')
            ->first(['created_at', 'ip']);

        $base = self::fromModel($user);

        $base->apiKeysCount = (int) $user->api_keys_count;
        $base->sshKeysCount = (int) $user->ssh_keys_count;
        $base->passkeysCount = (int) $user->passkeys_count;
        $base->oauthConnectionsCount = (int) $user->oauth_connections_count;
        // Fortify's check, not `two_factor_secret !== null`: with confirmation required, a setup
        // the user abandoned halfway has a secret and is not enabled.
        $base->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
        $base->lastLoginAt = $lastLogin?->created_at;
        $base->lastLoginIp = $lastLogin?->ip;
        $base->resources = UserResourcesData::forUser($user);

        return $base;
    }
}
