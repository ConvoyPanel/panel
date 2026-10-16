# Permissions, sub-users and guests

Reference for three connected authorization models:

1. **Server permissions** — what a non-owner may do on one server.
2. **Guest accounts** — how someone who is not a customer of the provider gets an account.
3. **Admin roles** — fine-grained replacement for the blanket `root_admin` bit.

All three share one vocabulary shape, `<resource>.<action>`, and one enforcement pattern:
a permission is derived from the request and checked against the actor's grants, the same
way `App\Support\Api\ScopedTokenAbilities` derives a token ability from the request path.

## Relationship to API token abilities

Token abilities and permissions answer different questions and are **both** enforced:

| | Token abilities (`ScopedTokenAbilities`) | Permissions (this document) |
| --- | --- | --- |
| Scopes | one credential | one account |
| Granularity | resource + read/write | resource + action |
| Set by | the token's creator, at mint time | the server owner, or an operator |
| Enforced in | `EnforceTokenAbilities` middleware | policies (client) and `EnforceAdminPermissions` (admin) |

The effective authority of a request is the **intersection** of the two. A token can never
widen what its owner may do, and a permission can never widen what the token was scoped to.
Web-session requests carry no token, so only permissions apply.

The two vocabularies share resource keys (first path segment) so the admin API's ability
list and permission list line up rather than drifting.

### Existing drift to correct

- `TokenAbilities::RESOURCES` lists `template-groups`, which has no route in
  `routes/api-admin.php`; templates became images.
- `TokenAbilities::RESOURCES` omits `isos`, `images`, `image-groups`, `storages`,
  `server-presets`, `relays`, `settings`, `tokens`, `audit-logs`, `clusters` and `backups`.
  Every one of those currently falls through `requiredFor()` to `*`, so a scoped application
  token reaches none of them.

---

## 1. Server permissions

### Model

| Table | Columns |
| --- | --- |
| `server_subusers` | `id`, `server_id` (FK cascade), `user_id` (FK cascade), `permissions` (json), `created_by`, `created_at`, `updated_at`, unique `(server_id, user_id)` |

- `App\Enums\Server\ServerPermission` is the closed catalog; `permissions` stores its values.
- `Server::subusers()`, `User::serverShares()`.
- `Server::scopeOwnedBy()` gains `orWhereHas('subusers', …)`. Its docblock already designates
  itself the single source of truth for client-facing server visibility, so every listing
  inherits sub-user access from that one change.
- `Client\Server\AuthenticateServerAccess` admits owner, sub-user, or an account holding
  `admin.servers.manage`; everyone else gets 404, not 403.

The owner always holds every permission implicitly. Nothing is stored for the owner.

### Enforcement

`App\Policies\ServerPolicy::before()` returns `true` for the owner and for an account with
`admin.servers.manage`. Its `__call()` fallback stops being a no-op: it resolves the ability
name to a `ServerPermission` and answers from the sub-user's grant. An ability with no
mapping resolves to `null`, which denies — new endpoints are closed by default.

`BackupPolicy` is dead code: every backup request calls `can(…, $server)`, which resolves to
`ServerPolicy`. It is deleted, and the three ambiguous abilities it implied (`create`,
`restore`, `delete` on a `Server`) are renamed to `createBackup`, `restoreBackup`,
`deleteBackup`.

### Permission catalog

Every row maps to a route in `routes/api-client.php`. Reads are grouped with the write they
support, because a screen that cannot read its own state is not a usable grant.

| Permission | Policy ability | Endpoints |
| --- | --- | --- |
| *(implicit, every sub-user)* | — | `GET /servers/{server}`, `/state`, `/deployment`, `/addresses`, `/resources` |
| `activity.read` | `viewActivity` | `GET /audit-logs` |
| `statistics.read` | `viewStatistics` | `GET /statistics` |
| `power.start` | `sendPowerCommand` (`start`, `resume`) | `POST /power` |
| `power.stop` | `sendPowerCommand` (`shutdown`, `suspend`) | `POST /power` |
| `power.restart` | `sendPowerCommand` (`restart`) | `POST /power` |
| `power.kill` | `sendPowerCommand` (`kill`, `reset`) | `POST /power` |
| `console.session` | `createConsoleSession` | `POST /create-console-session` |
| `console.configure` | `configureConsole` | `GET`/`POST /settings/hardware/serial-console`, `GET`/`POST /settings/hardware/display-console` |
| `backup.read` | `viewBackups` | `GET /backups` |
| `backup.create` | `createBackup` | `POST /backups` |
| `backup.restore` | `restoreBackup` | `POST /backups/{backup}/restore` |
| `backup.delete` | `deleteBackup` | `DELETE /backups/{backup}` |
| `firewall.read` | `viewFirewall` | `GET /firewall/options`, `/rules`, `/refs`, `/macros`, `/log` |
| `firewall.update` | `manageFirewall` | `PUT /firewall/options`, `POST /firewall/rules`, `PUT /firewall/rules/{position}`, `PUT /firewall/rules/{position}/move`, `DELETE /firewall/rules/{position}` |
| `settings.rename` | `rename` | `POST /settings/rename` |
| `settings.boot-order` | `updateBootOrder` | `GET /settings/hardware/storage`, `PUT /settings/hardware/boot-order` |
| `settings.media` | `manageMedia` | `GET /settings/hardware/isos`, `POST /settings/hardware/isos/{iso}/mount`, `POST /settings/hardware/isos/{iso}/unmount` |
| `settings.network` | `updateNetworkSettings` | `GET`/`PUT /settings/network` |
| `settings.auth` | `updateAuthSettings` | `GET`/`PUT /settings/auth` |
| `settings.reinstall` | `reinstall` | `GET /settings/image-groups`, `POST /settings/reinstall`, `POST /retry-installation` |

`GET /settings/hardware/storage` is reachable with either `settings.boot-order` or
`settings.reinstall`; it is a plain device read that both screens open with, and the policy method
`viewStorage` is what accepts either.

Reads without a write beside them are gated with route middleware (`can:<ability>,server`); writes
keep the form-request `authorize()` the codebase already uses. The sub-user group carries both, so
neither half depends on the other still being there.

#### Issue #96 entries with no endpoint

- **Kill and shutdown as one item.** They are separate `PowerCommand` cases with different
  blast radius, so they are separate permissions.
- **Change hostname / displayname as two items.** `POST /settings/rename` writes both in one
  transaction (`SettingsController::rename` calls `CloudinitService::setHostname` and
  `$server->update`). One permission, `settings.rename`.

#### Endpoints with no entry in issue #96

`/firewall/**`, `/statistics`, `/resources`, `/addresses`, `/audit-logs`, `/deployment`,
`/settings/reinstall`, `/retry-installation`, `/settings/hardware/serial-console`,
`/settings/hardware/display-console`, and ISO **unmount**. All are covered above.

#### Deliberately not delegable

`/subusers/**` is owner-only, through the policy ability `manageSubusers`, which nothing but
ownership (or `servers.manage`) satisfies. A sub-user cannot see, add, edit or remove sub-users,
because a permission to grant permissions is a permission to grant every permission. Server
deletion, suspension and resource limits stay admin-only and are not in this catalog.

### Sub-user endpoints

| Method | Path | Notes |
| --- | --- | --- |
| `GET` | `/api/client/servers/{server}/subusers` | owner only |
| `POST` | `/api/client/servers/{server}/subusers` | `{ email, permissions[] }` |
| `PATCH` | `/api/client/servers/{server}/subusers/{subuser}` | `{ permissions[] }` |
| `DELETE` | `/api/client/servers/{server}/subusers/{subuser}` | |

`POST` resolves `email` to an existing account. If none exists:

- guests enabled → create a guest account, issue a `UserInvite` through the existing
  `UserInviteService`, notify with `UserInvited`, and return the link alongside the row the
  same way `Admin\UserController::store` does (mail is not proof of delivery);
- guests disabled → 422, `No account exists for that email address.`

`UserInviteService::issue()` is `updateOrCreate` by `user_id`, so there is exactly one
outstanding invite per account and re-inviting invalidates the previous link. No second
invite system is introduced.

Sharing with yourself, and sharing a server you do not own, are both 422.

### Audit events

`server.subuser.added`, `server.subuser.updated`, `server.subuser.removed`, subject the
server, properties the invitee's email and the permission list. Client-visible, so the owner
sees them in the server's activity feed.

---

## 2. Guest accounts

Convoy has no public signup; accounts come from the provider's automation. A guest is an
account that exists only to hold shares.

### Separation

`users.type`, an `App\Enums\User\UserType` backed enum: `standard` (default) or `guest`.
A column, not a derived state, so every list, count and filter can see it without a join.

Separation is visible everywhere an admin looks:

| Surface | Treatment |
| --- | --- |
| `GET /api/admin/users` | `AllowedFilter::exact('type')`; `AllowedSort::field('type')` |
| Admin user list | a **Guest** badge in the name cell, and a type filter in the `PageToolbar` |
| `GET /api/admin/overview` | `users` splits into `standardUsers` and `guestUsers` |
| `UserData` | `type` on every payload, including `createdBy` on an API key |
| Audit log | actor label carries the type; `admin.user.type-changed` records promotions |
| `App\Console\Commands\User\MakeUserCommand` | prints the type in its summary table |

### What a guest can and cannot do

| | Guest |
| --- | --- |
| Own a server | No. `Server::user_id` must reference a `standard` account; enforced in `StoreServerRequest` and on owner change. |
| See other servers | No. `scopeOwnedBy` returns only servers with a `server_subusers` row for them. |
| Billing / provisioning | Not applicable. Convoy carries no billing; the SSO endpoint (`POST /api/admin/users/{user}/generate-sso-token`) refuses a guest, so a billing integration cannot land one in a customer session. |
| Manage own account | Yes — password, 2FA, passkeys, SSH keys, sessions. Securing the account is the guest's own job. |
| Mint account API tokens | Yes, scoped by `AccountTokenAbilities` and still filtered by sub-user permissions. |
| Hold an admin role | No. `users.admin_role_id` must be null for a guest. |

### Lifecycle

- **Share revoked, or shared server deleted.** The `server_subusers` row cascades. The guest
  account survives with zero servers. It is not auto-deleted: deleting it would take its
  audit trail with it, and the same person is likely to be re-invited. The admin user list
  shows guests with no remaining shares so they can be cleared deliberately.
- **Server transferred to another owner.** All `server_subusers` rows for that server are
  deleted and audited. The new owner did not invite them.
- **Promotion.** An admin sets `type` to `standard` on the user detail screen. The reverse is
  refused while the account owns a server or holds an admin role.

### Global switch

`App\Settings\PermissionSettings`, group `permissions`:

```php
// Whether a server owner may share a server with an address that has no account yet,
// creating a guest account for it.
public bool $allow_guest_accounts = false;
```

Off by default: an install that has never had guests should not gain them on upgrade.

The settings endpoint also returns the number of guest accounts that exist, so the screen can
state what flipping the switch off actually locks out rather than describing the policy.

While off, no guest account can be created **and no guest can sign in**. A switch that only
stopped new guests would leave an operator who flips it off with no way to close the door on
the ones already through. Existing shares are retained, not deleted, so turning it back on
restores access.

> Settings properties carry `//` line comments only. A prose docblock without an `@var` tag
> silently disables the property's cast and breaks hydration of the whole settings group,
> surfacing as "Typed property … must not be accessed before initialization" on an unrelated
> field.

---

## 3. Admin roles

### Shape: one named role per account

A user holds zero or one admin role. A role is a name plus a set of admin permissions.

Chosen over per-user permission sets and over many-roles-per-user:

- The question an operator asks is "what can Bob do?", and with one role the answer is the
  role's name — legible in a table column, on the user detail page, and in the audit log.
- Many-roles-per-user makes effective permissions a union nobody can see at a glance, and
  buys only the combinations a custom role already covers. An operator who needs support and
  network makes that role once.
- Per-user checkboxes make every account a bespoke artifact and nothing is reviewable.

`spatie/laravel-permission` is **not** installed and is not proposed. Its model (guards,
teams, many-to-many roles and direct permissions) is broader than this needs, and the
enforcement pattern here mirrors `EnforceTokenAbilities`, which already exists. No new
dependency.

### Model

| Table | Columns |
| --- | --- |
| `admin_roles` | `id`, `uuid`, `name`, `description`, `permissions` (json), `is_system` (bool), `is_superadmin` (bool), timestamps |
| `users` | gains `admin_role_id`, nullable FK, `nullOnDelete` |

`users.root_admin` is **dropped**. `User` keeps a `root_admin` accessor/mutator over
`admin_role_id` so existing call sites (`User::factory()->create(['root_admin' => true])`,
`MakeUserCommand`, `StoreUserRequest`) keep working and keep meaning "Superadmin". Reading
it is `adminRole?->is_superadmin`; writing it assigns or clears the Superadmin role.
`Admin\UserController`'s `AllowedSort::field('rootAdmin', 'root_admin')` becomes a sort on
the joined role name.

`User::hasAdminPermission(string $permission): bool` is the only read API. A superadmin role
answers `true` to everything.

### Permission catalog

Resource keys are the first path segment under `api/admin/`, matching
`ScopedTokenAbilities`. `manage` implies `read` for the same resource, the same way
`{resource}:write` implies `{resource}:read`.

| Permission | Endpoints |
| --- | --- |
| `overview.read` | `GET /overview`, `GET /version`, `POST /version/check` |
| `audit-logs.read` | `GET /audit-logs` |
| `locations.read` / `locations.manage` | `/locations/**` |
| `nodes.read` / `nodes.manage` | `/nodes/**`, `/storages`, `/storages/{storage}/consumers`, `/clusters/{cluster}/unflag` |
| `servers.read` / `servers.manage` | `/servers/**`, `/server-presets/**`, `DELETE /backups/{backup}` |
| `servers.power` | `POST /servers/{server}/power` |
| `address-block-groups.read` / `.manage` | `/address-block-groups/**` |
| `image-groups.read` / `image-groups.manage` | `/image-groups/**`, `/images/**` |
| `isos.read` / `isos.manage` | `/isos/**` |
| `users.read` / `users.manage` | `/users/**` including `/users/{user}/api-keys`, `/ssh-keys`, `/passkeys`, `/oauth-connections`, `/two-factor`, `/invite`, and `/admin-roles/**` |
| `users.impersonate` | `POST /users/{user}/generate-sso-token` |
| `anchors.read` / `anchors.manage` | `/anchors/**`, `/relays/**` |
| `tokens.manage` | `/tokens/**` |
| `settings.read` / `settings.manage` | `/settings/**` |

`servers.power` and `users.impersonate` are carved out of their resource because the
personas that need them are not the personas that should hold `manage`: support staff reboot
without deleting, and signing in as a customer is impersonation rather than administration.

### Enforcement

`App\Support\Admin\AdminPermissions` mirrors `ScopedTokenAbilities`:

- `requiredFor(Request)` reads the first path segment after `api/admin/` (or
  `api/application/`) and picks `read` for `GET`/`HEAD`, `manage` otherwise;
- an override table handles the carve-outs (`POST /servers/{server}/power` →
  `servers.power`, `POST /users/{user}/generate-sso-token` → `users.impersonate`);
- an unknown segment requires `*`, so a route added without a catalog entry is reachable
  only by a superadmin. Closed by default.

`AdminAuthenticate` keeps admitting `SystemActor` (an application token is authorization in
itself) and otherwise requires an account with an admin role. `EnforceAdminPermissions` runs
after it and checks the derived permission. A `RouteAdminPermissionCoverageTest` asserts
every route in `routes/api-admin.php` resolves to a catalog entry rather than to `*`.

The frontend reads `adminPermissions` off `UserData` and hides admin nav sections the account
cannot reach, so nothing in the sidebar leads to a 403.

### Default roles

Seeded as `is_system` (renameable, not deletable, permissions not editable):

| Role | Permissions |
| --- | --- |
| **Superadmin** | everything (`is_superadmin`) |
| **Support** | `overview.read`, `audit-logs.read`, `nodes.read`, `servers.read`, `servers.power`, `users.read`, `isos.read`, `image-groups.read`, `locations.read`, `address-block-groups.read` |
| **Billing** | `overview.read`, `users.read`, `users.manage`, `servers.read`, `audit-logs.read` |
| **Network** | `overview.read`, `nodes.read`, `nodes.manage`, `address-block-groups.read`, `address-block-groups.manage`, `locations.read`, `locations.manage`, `anchors.read` |

Custom roles are created from the same catalog, and the usual way to make one is to **duplicate**
the closest built-in role and edit the copy: an operator who needs Support plus Network does it in
two clicks rather than reassembling ten permissions from memory. That is what keeps one-role-per-
account from costing anything.

A built-in role's name and description are editable; its permissions are not, and it cannot be
deleted, so an install always has something recognisable to fall back to. Authoring a role at all
is restricted to a superadmin: `tokens.manage`, `settings.manage` and `users.impersonate` are each
a path back to full control, so an operator who could mint a role holding one could promote
themselves in a single step. Assigning an existing role needs only `users.manage`.

A role that accounts still hold cannot be deleted; deleting it would silently strip admin access
from everyone on it.

### Migration path for existing root admins

One migration, in order:

1. Create `admin_roles`; insert the four system roles.
2. Add `users.admin_role_id`.
3. `UPDATE users SET admin_role_id = <superadmin> WHERE root_admin = true`.
4. Drop `users.root_admin`.

Changing an account's role revokes its API tokens. Sanctum checks abilities against the token
rather than re-deriving them from the account, so a narrowed role would otherwise linger on a
credential minted under the old one. Changing the role on the account you are signed in as is
refused: the screen that would fix it is the one you just narrowed.

Every existing admin keeps exactly the access they had, under the name **Superadmin**.
Nobody is locked out, and no operator has to do anything at upgrade time. The `down()`
reverses it by writing `root_admin = admin_role_id IS NOT NULL AND is_superadmin`.

Migrating away from blanket access is then a deliberate act: an operator opens a user and
changes the role. The admin UI offers a role select and no longer offers a root-admin
checkbox, so Superadmin is a named choice rather than the default.

---

## Layout reference

Admin and client screens both render inside `AppLayout`
(`resources/scripts/components/layouts/AppLayout.tsx`): `main` is
`max-w-[1600px]`, and the content column inside it carries `p-4 sm:px-6`. Mockups are drawn
at **1600px** with a **1552px** content width.
