# Changelog

This file is a running track of new features and fixes to each version of the panel released starting with `v2.0.0`.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
follows [Semantic Versioning](https://semver.org) guidelines.

## Unreleased

v5 rewrites the panel: a new frontend, PostgreSQL instead of MySQL, Anchor instead of Coterm, and a container install.

### Upgrading from 4.x

The upgrade is a one-time cutover with a maintenance window, not an in-place update. Customers' virtual machines keep
running throughout. Only the panel is offline.

- **Move the database to PostgreSQL 17.** MySQL is no longer supported.
  [`database/cutover/RUNBOOK.md`](database/cutover/RUNBOOK.md) copies the data across and runs the renaming migrations.
- **Add an image for every OS you offer.** Templates are gone and are not migrated. A template row is only a VMID,
  while an image needs the disks and their hardware profile.
- **Enroll each Proxmox host with Anchor.** Coterm is gone. Nodes carried over from 4.x work for everything except the
  console until their host is enrolled.
- **Update your billing module and other API clients** for the changes in the table below.
- **Check mail after upgrading.** SMTP `MAIL_*` values are imported into the panel once and not read again.

#### Breaking API changes

| What | 4.x | v5 |
| --- | --- | --- |
| Power commands | `PATCH /api/client/servers/{server}/state` with `state` | `POST /api/client/servers/{server}/power` with `command`. `GET …/state` is unchanged. The admin and application APIs have the same pair. |
| Server status | `status` | `lifecycle`, `suspendedAt` and `powerState` |
| Statistics | `GET /api/client/servers/{server}/statistics` returns a bare array | The array is wrapped in `data` |
| SSO | `generate-sso-token` returns `token`, redeemed at `consume-token` | Returns `link`, a single-use signed URL that expires after `sso.link_ttl`. `consume-token` is gone. Each use is audited. |
| IP pools | `/api/application/address-pools` | Address block groups |
| Nodes | `POST /api/application/nodes` | Removed. Nodes join through Anchor enrollment. |
| Templates | `/api/application/nodes/{node}/template-groups` | Removed. Servers are built from images. |

### Added

#### Installation and operations

- **One-command install.** `curl -fsSL https://install.convoypanel.com | sudo bash` sets up Docker, writes
  `/opt/convoy`, starts the stack and prints a URL and a temporary admin password. It runs on Debian 12/13, Ubuntu
  22.04/24.04, AlmaLinux 9/10 and Rocky Linux 9/10, on x86_64 or arm64. See [`docs/deployment.md`](docs/deployment.md).
- **Container image.** `ghcr.io/convoypanel/panel` runs as three containers, all required:
  - `web`: FrankenPHP, with automatic TLS over ACME.
  - `worker`: Horizon. Every Proxmox action is a queued job.
  - `scheduler`: runs the node and Anchor polls.
  - PostgreSQL 17 and Redis 8 are bundled, and either can be swapped for your own.
  - `latest` moves only on a release. Set `CONVOY_VERSION` to pin one.
- **`convoyctl`.** `convoyctl upgrade` backs up the database, pulls new images, restarts and waits for the panel to
  report healthy. It also has `backup`, `logs`, `ps`, `artisan`, `shell` and `horizon`.
- **Configuration reference.** Every setting and its default is in `.env.reference` and explained in
  [`docs/configuration.md`](docs/configuration.md). `.env.example` keeps only what an install must set.
- **Update notices.** An hourly check of GitHub releases shows a newer version in Settings, on the dashboard and as a
  banner ([#72](https://github.com/ConvoyPanel/panel/issues/72)).
- **Audit log.** An append-only record of panel activity, with its own screen. It replaces the old activity log, which
  never recorded anything ([#53](https://github.com/ConvoyPanel/panel/issues/53)).
  - Sign-in, two-factor, credential and token events are kept for good. Others are pruned after a configurable window.
  - Entries keep the actor's name, so they survive the account being deleted.
  - `reveal_staff_identity` (off by default) shows customers an administrator's name instead of "Staff".
- **Mail settings in the panel.** SMTP is set under Settings, with a test that sends a real message and shows the mail
  server's own error. The password is write-only and encrypted at rest. Emails use the panel's own styling, dark mode
  included.
- **Admin dashboard.**
  - KPI tiles, and fleet capacity for memory, storage and IP addresses.
  - An attention card that links to each record needing a look.
  - Servers by state, and memory and datastore usage per node.
  - Trends and sparklines need `VICTORIAMETRICS_URL`. Everything else works without it.

#### Nodes and Anchor

- **Anchor replaces Coterm.** An agent on each Proxmox host that carries the browser consoles and reports on its host.
  - To add a node, run the install command from the panel on the host as root. Then approve it and pick its location,
    oversubscription and, for now, its Proxmox API token.
  - Enrollment keys are single-use and expire by default, and can be revoked.
  - The installation secret rotates on every enrollment, so a leaked `anchor.toml` stops working.
  - An Anchor can reach the panel at a URL other than `APP_URL`.
  - The Anchors screen lists each agent with its endpoint, version and when it was last heard from.
- **Node status.** The scheduler polls every node each minute with a real Proxmox API call. The same poll records
  each guest's power state.
  - An unreachable node says why: powered off, firewalled, untrusted TLS certificate and so on.
  - A status the poll hasn't refreshed reads Unknown, so a stopped scheduler or queue shows up.
- **Node pages.** Overview, servers and settings. Settings can test the saved connection and fill in sockets, cores,
  CPUs and memory from the host.
- **Network interfaces.** Create, edit and delete them, and see each node's bridges and VLANs in a topology view.
- **Clusters.** Each node's Proxmox cluster is recorded, identified by its CA fingerprint so two clusters with the same
  name stay apart. VMIDs are checked for uniqueness per cluster.
- **Guests that move are followed.** When Proxmox moves a guest through HA recovery or migration, the poll moves the
  server to its new node, or flags it for an administrator if the move is ambiguous. Before, the server went stale
  without any sign ([#161](https://github.com/ConvoyPanel/panel/issues/161), in part).

#### Servers

- **Built from images.** An image definition holds the OS type, hardware settings and minimum resources. An image
  version is one build of its disks, and each server records the version it came from.
  - Hardware settings use a form generated from the node's own Proxmox API schema, so a bad key is refused on save
    instead of at first boot.
  - Images download through Proxmox's `download-url`, which verifies the checksum. A file can also be uploaded and
    served to the node over an expiring signed link.
- **Panel-wide ISO library.** One entry per ISO, fetched onto a node the first time it is mounted there.
- **Multiple disks.** Add extra disks at creation (`limits.disks[]`) or later from the admin Disks tab, then grow or
  remove them. Capacity is checked per storage across all of a server's disks.
- **Shorter create form.** Name, hostname, owner, node, storage, image and password. The rest sits in summaries that
  open for editing. A build can be saved as a preset and applied in one pick.
- **Server pages.** Overview with live CPU and memory trends, Resource usage, Networking, Storage, ISO library,
  Firewall, Backups, Security and Rebuild. Rebuild shows install progress step by step, with Retry on failure.
- **Firewall tab.**
  - Inbound and outbound chains, each ending in its default policy.
  - Logging per direction, drag-to-reorder, and an on/off switch per rule.
  - A rule dialog that asks for direction, allow or block, traffic, and source or destination. It offers the cluster's
    aliases, IP sets and groups by name.
  - Setting inbound to Drop with no rule allowing SSH asks for confirmation.
  - A parsed packet log.
  - The same is available over the client API.
- **Consoles.** A split button that remembers the last type used.
  - Display console with a keys bar, scaling options and two-way clipboard.
  - Serial terminal console. New servers get the serial device it needs, and older ones can add it from the picker.
  - A failed console says why, such as the agent refusing, the agent being unreachable from the browser, or the guest
    having no display. It offers the fix where there is one.
- **Power state.** Client and admin server lists show running, stopped or unknown.
  - Power actions are tracked by their Proxmox task.
  - A second command while one is running gets a 409 `power_action_in_progress`.
  - One progress toast follows the action, through a reload, to success or failure.
- **Backups.**
  - A Create Backup dialog asks for a name and keeps mode, compression and lock under Advanced.
  - The quota sits in the backups view.
  - Creating is disabled, with the reason, when the node has no backup-capable storage.
  - A failed backup says why. The owner sees the reason, and administrators also see Proxmox's own message.
- **Guest agent disk usage.** The overview reads disk usage from the QEMU guest agent when it's running, and shows the
  disk limit when it isn't.
- **Admin server page.** Live state, specification, copyable owner, VMID and UUID, power controls, and a link to the
  client view of the same server. The admin servers list has bulk power actions.

#### Storage

- **Several storages per node.** ISOs and backups belong to a storage instead of a node.
- **Registered from Proxmox.** Pick a storage the node reports. Proxmox supplies its name, capacity, content types and
  whether it's shared. You add a display name and reserved headroom.
- **Real capacity.**
  - Live from Proxmox, or the last recorded figure when the node is down. The API says which in `capacitySource` and
    `observedAt`.
  - Shows physical use, what Convoy has allocated and what is untracked. Untracked space isn't shown for
    thin-provisioned and deduplicating backends.
- **Reserved headroom** is enforced when servers are created. An unreachable node doesn't block creation.
- **Shared storage** is one entry per cluster definition, linked to every node that reaches it. A fleet-wide Storage
  screen lists each pool once.
- **Storage page.** Lists what fills a storage, largest first: servers, backups and ISOs. Each can be deleted from
  there, and deleting a server needs its name typed.

#### Networking and IPAM

- **Address blocks and groups** replace address pools. Groups attach to nodes. Existing pools are migrated.
- **Allocation.**
  - Numeric order, through one locked query, so two servers created at once can't get the same address.
  - Large IPv4 blocks and all IPv6 blocks create addresses as needed, which removes the 10,000-address IPv6 limit.
- **Address states.** Available, assigned or reserved. Network, broadcast and gateway are reserved automatically and
  can't be released. Administrators can reserve any other.
- **Block map.** A block of up to 4,096 addresses shows as a grid. Click or drag cells to select addresses for bulk
  reserve, release or delete. Every block shows its utilisation.
- **Assigning addresses** starts from the server and offers the next free one.
- **VLANs** ([#150](https://github.com/ConvoyPanel/panel/issues/150)). VLAN-aware interfaces with a default tag,
  per-server overrides, and a per-node list of declared VLANs that a server's tag must come from.

#### Accounts and sign-in

- **Passkeys** for sign-in and identity confirmation, named after the authenticator that created them, such as
  "1Password" or "YubiKey 5 Series".
- **Two-factor** with an authenticator app, one sign-in challenge for whichever second factor an account has, and
  recovery codes. Passkey-only accounts get recovery codes too.
- **Single sign-on** with Google, GitHub, GitLab or any OpenID Connect provider (Keycloak, Authentik, Okta, Auth0,
  Azure AD and others), each enabled in configuration. Accounts link providers under Connected Accounts.
- **Account pages.** A profile with a cropped picture, an SSH keychain, personal API tokens, and active sessions that
  can each be signed out. Deleting a user ends their sessions.

#### Administration

- **Users screen.** List, search, create and edit accounts. An administrator can't demote or delete their own account.
- **Invites.** A user created without a password gets a link to set their own. It's emailed when mail is set up and
  always shown to copy. A new link voids the old one.
- **Credential review.** See and revoke an account's passkeys, SSH keys, API tokens, sign-in providers and two-factor.
  Administrators can't add credentials for another user.
- **Settings screen.** Bandwidth defaults, mail, updates, and the account policy that sets what users may change about
  their own account, pictures included.
- **Command palette**, and admin navigation grouped by area.

#### API

- **Full Application API.** `/api/application` serves with a bearer token the same routes `/api/admin` serves with a
  session, so it covers IPAM, storage, network interfaces and the overview too.
- **Application tokens**, under Tokens in the admin area.
  - Owned by the panel, so they survive the deletion of the administrator who created them.
  - Abilities per resource: `servers:read`, `servers:write`, `servers:*` or `*`. Write includes read.
  - Can be restricted to networks.
  - Can never create or revoke tokens.
- **Personal access tokens** for the client API. They can't reach account settings.
- **Consistent errors.** `{ "message": "…", "code": "…" }` with a stable code.
- **Safe config writes.** Writes carry Proxmox's config digest, so a change made on the node in between gets a 409
  instead of being overwritten.

### Changed

- **PostgreSQL 17 only.** See [Upgrading from 4.x](#upgrading-from-4x).
- **PHP 8.4, Laravel 12 and React 19.**
- **New frontend.** A sidebar that switches to a server's own pages when you open one, light and dark themes, and
  layouts that work on a phone. Page subtitles are gone, and page actions sit on a toolbar under the heading.
- **Bandwidth limits** are a speed cap plus an overage penalty that applies once the quota is used. The penalty either
  throttles to a set rate or disconnects the network interface.
  - Both resolve per server, then per node, then from the default in Settings.
  - Each server's quota resets on its own day of the month.
  - Speed caps are sent to Proxmox in its decimal MB/s.
- **Root passwords** are set through cloud-init on every OS, Windows included. The 4.x path that set them live through
  the QEMU guest agent is gone.
- **Storage sizes** are shown in IEC units (MiB, GiB).

### Removed

- **Templates, Coterm, and adding nodes by hand.** See [Upgrading from 4.x](#upgrading-from-4x).

### Fixed

- **Checksummed downloads always failed, ISOs included.** Convoy sent `algorithm` where Proxmox expects
  `checksum-algorithm`.
- **Network sync reset firewall default policies to ACCEPT** on every address change, rebuild and sync. It now only
  turns the firewall and IP filter on or off.
- **Backups requested as locked were saved unlocked.** The request was validated as `is_locked` but read as `locked`.
- **A disk resize during build could fail without anyone noticing**, leaving the guest at the image's disk size.
  Convoy now waits for the Proxmox task and retries one that timed out.
- **A killed worker could leave a server's jobs stuck forever.** Job locks now expire, and a job that finds one held
  waits and retries.

### Security

- **Identity confirmation** before creating or deleting API tokens and SSH keys, setting a server's root password, or
  rebuilding a server. A confirmation lasts five minutes. Without it, a stolen session could mint a full-access token
  that outlives logout and a password change.
- **Changing your password** signs out every other session, replaces the remember-me token and emails the account
  owner.
- **Password policy.** At least 12 characters and at most 72 bytes, with no composition rules.
  - Checked against known breaches through Have I Been Pwned's k-anonymity API, skipped when it can't be reached.
  - Passwords set by an administrator follow the same policy.
- **Two-factor** counts as enabled only once a code from the authenticator has been entered. Accounts that already
  have a secret carry over as confirmed and are still challenged at sign-in.
- **Profile pictures** are decoded, cropped, scaled to at most 512 px and re-encoded as WebP, so uploaded bytes are
  never served. Images over 50 megapixels are refused.
- **Invite tokens** are stored only as a hash. An unknown, used or expired link gets the same answer.

## v4.6.1

### Security

- Fixed the client API resolving `{iso}` on its own rather than through the server it is nested under, which let a mount
  request name an ISO belonging to a node the caller has no server on. No practical exposure followed from it: an ISO is
  addressed by UUID, and the only place Convoy shows a non-administrator an ISO's UUID is the media list for a server on
  that ISO's own node, where mounting it is already allowed. Hidden ISOs were refused either way. The fix restores the
  scoped route-model binding that the rest of the client API relies on to keep one server's URL from reaching another
  server's resources.

  This describes the 4.x ISO model, in which a row belonged to one node and the same disc on four nodes was four rows.
  The library is panel-wide after 4.x: an ISO is offerable on every node, so there is no node boundary left for a mount
  request to cross and no relationship for the binding to scope through. The mount and unmount endpoints opt out of
  scoping deliberately there, gated on the hidden flag instead, which `RouteScopingTest` records and enforces.

### Fixed

- Fixed servers with no bandwidth limit being incorrectly rate limited to 1 MB/s ([#157](https://github.com/ConvoyPanel/panel/issues/157)).

## v4.6.1-rc.1

### Changes

- Fixed servers with no bandwidth limit being incorrectly rate limited to 1 MB/s ([#157](https://github.com/ConvoyPanel/panel/issues/157)).

## v4.2.4

### Changes

- Fixed a bug where initiating a backup with no compression fails. #87

## v4.2.3

### Changes

- Updated code for applying rate limits to NIC. Convoy will no longer override settings other than ratelimit, NIC
  model (e.g., e1000, vmxnet3, virtio, etc.), and firewall status.

#### From v4.2.2-rc.2

- Fix US keyboard characters validation #80
- Fixed a visual bug on the bandwidth usage card where the text wasn't centered.

#### From v4.2.2-rc.1

- Fix special character support in environment file.
- Added checks in server creation to use unique VMID. #78
- Add error messages instead of generic server error messages. #49
- Scope route model binding by default to prevent unauthorized access of related resources.
- Removed a lot of dead code.
- Added more tests (getting closer to full release! 😁😩).

#### From v4.2.1-rc.1

- Potential fix for disk resize timeout?

## v4.2.2-rc.2

### Changes

- Fix US keyboard characters validation #80
- Fixed a visual bug on the bandwidth usage card where the text wasn't centered.

## v4.2.2-rc.1

### Changes

- Fix special character support in environment file.
- Added checks in server creation to use unique VMID. #78
- Add error messages instead of generic server error messages. #49
- Scope route model binding by default to prevent unauthorized access of related resources.
- Removed a lot of dead code.
- Added more tests (getting closer to full release! 😁😩).

## v4.2.1-rc.1

### Changes

- Potential fix for disk resize timeout?

## v4.2.0-beta

### Changes

- Added server UUID copy to clipboard button in the admin area server table.
- Added ability to toggle TLS verification per node basis.

## v4.1.0-beta

### Changes

- Removed the `DB_ROOT_PASSWORD` variable from the environment file. It is now automatically generated, but we still
  aren't planning on using it.
- Added health checks to the Docker compose configuration to prevent the containers from exploding when its dependencies
  don't start up in time.

## v4.0.0-beta

If you use Convoy for a production or commercial environment/purpose, please subscribe to a
license [here](https://console.convoypanel.com). It supports my work, and you are also violating the license agreement
if you don't. Your deployment of Convoy may be disabled without warning if you don't adhere to the terms of the license
agreement.

### Changes

- **BREAKING**: Overhauled the IP address management system to add IP pools that can be shared among nodes. #51
- **BREAKING**: Fixed Coterm where it doesn't support multiple nodes #50
- Fixed inability to use special characters for Redis password.
- Fixed error when trying to parse a vm's disk that has no `size` attribute #48
- Fixed typo in the input labels on the node creation modal #42
- Fixed the mobile navigation menu where it won't automatically close when you click on a link #41
- Added ability to copy node and template IDs #40
- Fixed incorrect conversion from mebibytes to bytes of a server's bandwidth limit during manual server creation through
  the admin area UI #70
- Fixed missing SSO token creation endpoint
- Fixed bulk importing of IPv6 addresses #66
- Fixed inability to create servers with IP addresses.
- Fixed minor UI bug where addresses in IPAM won't optimistically update after making a change.
- Made the IPAM address table sort by descending.
- Removed API request throttling
- Increased Coterm session token lifetime from 30 seconds to a minute.
- Fixed cloning of VM's to the wrong storage location #64

## v4.0.0-rc.5

### Changes

- Fixed missing SSO token creation endpoint

## v4.0.0-rc.4

### Changes

- Fixed bulk importing of IPv6 addresses #66

## v4.0.0-rc.3

### Changes

- Fixed inability to create servers with IP addresses.
- Fixed minor UI bug where addresses in IPAM won't optimistically update after making a change.
- Made the IPAM address table sort by descending.

## v3.10.2-beta

### Changes

- Fixed cloning of VM's to the wrong storage location #64

## v4.0.0-rc.2

### Changes

- Removed API request throttling
- Increased Coterm session token lifetime from 30 seconds to a minute.
- Fixed cloning of VM's to the wrong storage location #64

## v4.0.0-rc.1

If you use Convoy for a production or commercial environment/purpose, please subscribe to a
license [here](https://console.convoypanel.com). It supports my work, and you are also violating the license agreement
if you don't. Your deployment of Convoy may be disabled without warning if you don't adhere to the terms of the license
agreement.

### Changes

- **BREAKING**: Overhauled the IP address management system to add IP pools that can be shared among nodes. #51
- **BREAKING**: Fixed Coterm where it doesn't support multiple nodes #50
- Fixed inability to use special characters for Redis password.
- Fixed error when trying to parse a vm's disk that has no `size` attribute #48
- Fixed typo in the input labels on the node creation modal #42
- Fixed the mobile navigation menu where it won't automatically close when you click on a link #41
- Added ability to copy node and template IDs #40

## v3.10.1-beta

### Fixes

- Fixed `workspace` image failing to build because PHP Composer hash changed from a recent update.

## v3.10.0-beta

### Additions

- Added ISO imports (thanks A LOTTTT Fro!!!)
- Added new logo courtesy of Yatin Manuel from [Halvex Inc.](https://halvex.net/)
    - This changelog was forgotten, so I will add it now

### Changes

- mind-numbing frontend refactorings

## v3.9.3-beta

### Fixes

- Fixed inability to successfully build the `workspace` container because the PHP Docker image recently upgraded from
  Debian 11 to 12 while Nodesource hasn't added stable support for Debian 12.

## v3.9.2-beta

### Fixes

- Fixed inability to save new password/ssh keys on Client > Server > Settings > Security > Authentication

## v3.9.1-beta

### Fixes

- Fixed coterm fields required when trying to update node information instead of updating Coterm information.

### Changes

- Reorganized frontend React Router routes

## v3.9.0-beta

The release candidate process for non-beta v3.9.0 was discontinued. Convoy will be keeping its beta status while more
improvements to the product are made.

If you are upgrading from v3.8.1-beta, keep note of these notices:

- v3.9.0-rc.9: :warning: This release may require you to regenerate your Proxmox API token credentials for each of your
  nodes with the implementation of encryption of node credentials in Convoy from this point on.
- v3.9.0-rc.3: :warning: This release makes changes to the data in your database. Rows that have a non-null `deleted_at`
  field in
  your `servers` and `users` table will be purged. If your routine relies on soft deletes, please make changes now to
  stop
  relying on them as they are removed in this update.

### Fixed

- Servers using IP addresses that they shouldn't have access to.

### Added

- Address pools (not in use yet)

### Changed

- IPAddress internal naming to Address
- SSO to use JWT instead of generating+saving tokens to the database

## v3.9.0-rc.9

:warning: This release may require you to regenerate your Proxmox API token credentials for each of your nodes with the
implementation of encryption of node credentials in Convoy from this point on.

### Added

- Coterm support (DEV PREVIEW)
- Encryption for PVE node credentials

### Fixed

- Potential security vulnerability allowing unauthorized users to access another user's server

## v3.9.0-rc.8

### Fixed

- Inability to reimport the same IP address across different nodes.

## v3.9.0-rc.7

### Fixed

- Password updating when marked optional in a server import

## v3.9.0-rc.6

### Fixed

- Email max length on the login screen

## v3.9.0-rc.5

### Fixed

- Password setting when rebuilding/creating a new server

## v3.9.0-rc.4

### Fixed

- Browser history hijacking on client server settings page

## v3.9.0-rc.3

:warning: This release makes changes to the data in your database. Rows that have a non-null `deleted_at` field in
your `servers` and `users` table will be purged. If your routine relies on soft deletes, please make changes now to stop
relying on them as they are removed in this update.

### Fixed

- Adding both a IPv4 and IPv6 address to a server

### Added

- Base code for adding translations/localizations to Convoy

### Removed

- Soft deletes from `servers` and `users` table

### Changes

- Some automatic refactors done by PHPStorm
- Renamed Media to Iso

## v3.9.0-rc.2

### Fixed

- Server reinstallations firing wrong job chain
- can sync server usages test
- ISO download progress monitor
- Backup creation progress monitor

## v3.9.0-rc.1

### Added

- Tests

### Changed

- Internal HTTP client from Guzzle to a wrapper of it from Laravel
- VM building, deletion, and server deletion logic
    - This should improve reliability of Convoy tremendously

## v3.8.1-beta

### Fixed

- Rate limit throttling

## v3.8.0-beta

### Changed

- Made system OS password optional if create server option isn't checked when creating a new server in the administrator
  area

## v3.7.5-beta

### Fixed

- English keyboard characters validation rule in create server modal in the administrator area.

### Changed

- Some minor refactoring that changed the namespacings of some files to improve code consistency.

## v3.7.4-beta

### Fixed

- Server power commands

## v3.7.3-beta

### Fixed

- Server deletion failing when the virtual machine is running

## v3.7.2-beta

### Fixed

- Error unmounting media (or ISOs) in the server control panel in the client area

## v3.7.1-beta

### Fixed

- Server bandwidth statistic not updating #14

## v3.7.0-beta

### Fixed

- Overallocation check logic whenever an administrator tries to update a server's build #16
- Issue where you can't use IPv6 for nameservers

### Changed

- Made account_password required by default for creating new servers and server installations
- Refactored all settings pages
- Refactored menu component to reduce bundle size
- Refactored Server Usages and Rate Limit sync for better scaling
- Minor frontend styling
- Refactored disk data transfer object
- All password inputs (except for Convoy user account password) has these two validation rules:
    1. `/^[A-Za-z0-9!@#$%^&*()_+\-=[\]{}|;\':",.\/<>?\\ ]*$/` for checking if the password contains only characters on
       the U.S. English keyboard.
    2. `/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/` for checking if the password contains 8
       characters, 1 uppercase, 1 lowercase, 1 number and 1 special character.

### Added

- Search bars in the administrator area
- Log rotation for Laravel log file
- Proxmox user pruning to prune expired temporary NoVNC users

## v3.6.2-beta

### Fixed

- IP Addresses being clipped if too long in the UI (x2)
- Hostname validation regex in frontend
- Viewport glitch when a server name or hostname is too long
- Validation rule not catching special characters from other languages for OS password validation

### Note

If you are developing automation software for Convoy, please implement these regular expressions in your code.
Otherwise, your code will error when you send invalid requests.

- server `account_password` validation
    - `/^[A-z0-9!@£$%^&*()\'~*_+\-]+$/` to detect special characters from other language
    - `/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/u` minimum password requirements
- server `hostname` validation
    - `/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/`

## v3.6.1-beta

### Fixed

- IP Addresses being clipped if too long in the UI
- Awkward delete address modal

## v3.6.0-beta

### Fixed

- IPv6 addresses wouldn't display in client area settings
- Confusing "account password" placeholder when creating a server in the admin area

### Added

- Ability to view IP addresses of a server on the dashboard in the client area

## v3.5.1-beta

### Added

- Support for hosting Convoy without a hostname

### Changed

- Default redis configuration in .env because some people were getting confused by the documentation

## v3.5.0-beta

### Changed

- Settings layout for client side server settings

### Fixed

- possibility that `address_ids` will cause an exception when it's null when creating a new server

## v3.4.0-beta

### Changed

- Refactored routes

### Added

- Navigation Bar Context. Now switching pages are even more seamless

## v3.3.0-beta

### Fixed

- Lack of cancel button when deleting an API key

### Added

- Ability for administrators to impersonate the client view for a server and also visit the server's configuration in
  the admin area.
- Warnings when creating a new node to disable privilege separation and grant root permissions.

## v3.2.0-beta

### Fixed

- Broken network syncing when updating an address's assigned server
- Text alignment for server counter on the users table in the admin area

### Added

- Hyperlinks to the owner on the servers table

## v3.1.2-beta

### Fixed

- Scoped routing bindings in RouteServiceProvider that were breaking some routes

## v3.1.1-beta

### Fixed

- Conflicting named routes that would break route optimization/caching

## v3.1.0-beta

### Fixed

- IP Address updating
- Server build updating

### Removed

- Option to sync or not sync network settings when deleting an IP address
    - The default behavior is always to sync

## v3.0.0-beta (Tuxedo)

### Fixed

- Server installs
- a bunch of other stuff

### Changed

- from proprietary hard-coded api tokens to Bearer tokens for the application/external api
- the whole entire frontend

### Added

- Server hostnames
- Node location grouping

### Notes

- This release is so big that I can't really summarize everything

![The maxwell cat meme is the mascot for v3](https://imgur.com/mowvogE.png)

## v2.0.3-beta

### Fixed

- Inability to delete IP address from the admin user interface

## v2.0.2-beta

### Fixed

- FQDN validator for the hostname field when adding a new node

## v2.0.1-beta

### Fixed

- Problem where validation errors for the SSH key wouldn't show up
- Bug where user couldn't unset a SSH key after saving one

## v2.0.0-beta (Bombay)

### Added

- Storing of CPU, memory, disk, snapshots, backups, and bandwidth limits
- Added server suspensions
- Added real-time status updates of server installs (though it will be deprecated in v3.x.x)
- Automatic bandwidth throttler when a user exceeds the bandwidth limit

### Changed

- Internally, server details are now passed around the application using Laravel Data by Spatie. Though in v3.x.x, we
  are planning on switching to Data Transfer Objects by Spatie. We pulled the wrong package and didn't realize until one
  month in using the package LOL.
- Virtual machines are now limited to one disk. Multiple disks may be supported when a daemon is available in the
  future.
- The built-in web server is now Caddy instead of Nginx. This provides auto SSL out of the box.

### Fixed

- The commands in the node viewing page for installing the VNC Broker and templates.

### Known Bugs

- The real-time server installation communication is known to be buggy and will be resolved in v3.x.x
- Editing the server field for IP Addresses will sometime result in the first server of the node to be used. This will
  be resolved in v3.x.x

![The Bombay cat breed is the mascot for v2](https://imgur.com/fP6oxn9.png)