<?php

namespace App\Data\Server;

use App\Enums\Server\ServerPermission;
use App\Models\ServerSubuser;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One person's access to a server, as the owner sees it.
 *
 * Deliberately thin on the account behind it: a name, the address the invitation went to, and
 * whether they have signed in yet. Whether they hold an admin role elsewhere in the panel, what
 * else they own, and when they last logged in are none of the sharing owner's business.
 */
#[MapInputName(SnakeCaseMapper::class)]
class ServerSubuserData extends Data
{
    public function __construct(
        public string $uuid,
        public int $userId,
        public string $name,
        public string $email,
        public ?string $avatarUrl,
        /** @var list<ServerPermission> */
        public array $permissions,
        /**
         * The account was created by this share and has not set a password yet, so the person on
         * the other end has not accepted. The owner needs it to tell "shared and ignored" from
         * "shared and active" without being handed the invite link.
         */
        public bool $isPending,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromModel(ServerSubuser $subuser): self
    {
        // `user.invite` too: the listing eager-loads both, and without it `isPending` would cost
        // a query per row.
        $subuser->loadMissing('user.invite');

        return new self(
            uuid: $subuser->uuid,
            userId: $subuser->user_id,
            name: $subuser->user->name,
            email: $subuser->user->email,
            avatarUrl: $subuser->user->avatarUrl(),
            permissions: $subuser->grantedPermissions(),
            isPending: $subuser->user->invite !== null,
            createdAt: CarbonImmutable::parse($subuser->created_at),
        );
    }
}
