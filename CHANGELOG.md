# Changelog

This file is a running track of new features and fixes to each version of the panel released starting with `v2.0.0`.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
follows [Semantic Versioning](https://semver.org) guidelines.

## Unreleased

This is v5, a rewrite of the panel: a new frontend, a new database engine, a new console agent and a new way to
install. Upgrading a 4.x panel is a one-time cutover with a maintenance window rather than an in-place update. The data
is copied from MySQL 8.0 to PostgreSQL 17, then a set of renaming migrations runs on it.
[`database/cutover/RUNBOOK.md`](database/cutover/RUNBOOK.md) walks through it. Customers' virtual machines keep running
throughout, and only access to the panel is down. Entries marked **BREAKING** need action from operators or from
anything that calls the API, such as a billing module.

### Added

#### Installation and operations

- Convoy installs onto a dedicated host with one command, `curl -fsSL https://install.convoypanel.com | sudo bash`. The
  installer sets up Docker if it is missing, writes `/opt/convoy`, starts the stack and prints a URL and a temporary
  password for the first administrator. Supported hosts are Debian 12/13, Ubuntu 22.04/24.04, AlmaLinux 9/10 and Rocky
  Linux 9/10, on x86_64 or arm64. See [`docs/deployment.md`](docs/deployment.md).
- The panel ships as one container image, `ghcr.io/convoypanel/panel`, run as three containers: `web` (FrankenPHP,
  which also terminates TLS and obtains certificates over ACME), `worker` (Horizon) and `scheduler`. All three are
  required: every Proxmox action is a queued job, and the node and Anchor polls are scheduled. PostgreSQL 17 and Redis 8
  are bundled, and either can be swapped for your own. The `latest` tag moves only on a release. Set `CONVOY_VERSION` to
  stay on one.
- `convoyctl` covers day-to-day operation. `convoyctl upgrade` backs up the database, pulls the new images, restarts
  the stack and waits for the panel to report healthy. Migrations run when the web container starts. It also has
  `backup`, `logs`, `ps`, `artisan`, `shell` and `horizon`.
- Every setting the panel reads is listed with its default in `.env.reference` and explained in
  [`docs/configuration.md`](docs/configuration.md), including about fifteen that were never documented, such as the
  timeouts for requests to Proxmox and Anchor. `.env.example` now holds only what an install must set.
- The panel tells administrators when a newer release is out. It checks GitHub hourly for the newest published,
  non-prerelease release and shows it in Settings, on the dashboard's attention card and as a banner. A source checkout
  reports `canary` and is never prompted ([#72](https://github.com/ConvoyPanel/panel/issues/72)).
- An audit log records what happens in the panel and has its own screen. Sign-in, sign-out, two-factor, credential and
  token events are kept for good, and everything else is pruned after a configurable window. Each entry keeps the
  actor's name, so it survives the account being deleted. Each kind of event decides whether customers see it, and
  the `reveal_staff_identity` setting (off by default) decides whether customers see an administrator's name or
  "Staff". It replaces the old activity log, which never recorded anything
  ([#53](https://github.com/ConvoyPanel/panel/issues/53)).
- Mail is configured in the panel instead of through `MAIL_*` variables. A test sends a real message using the unsaved
  form and shows the mail server's own error. The SMTP password is write-only and encrypted at rest. On upgrade,
  existing SMTP `MAIL_*` values are imported once, and the variables are not read after that. Emails (invites, password
  changes, the test message) use the panel's own styling, dark mode included.
- The admin dashboard has KPI tiles, fleet capacity for memory, storage and IP addresses, an attention card that names
  each record needing a look and links to it, servers by state, and a nodes table with memory and total datastore usage
  per node. Trends and sparklines come from VictoriaMetrics when `VICTORIAMETRICS_URL` is set. The dashboard works
  without it.

#### Nodes and Anchor

- Anchor, an agent that runs on each Proxmox host, replaces Coterm. It carries the browser consoles and reports on the
  host it runs on. To add a node, run the install command shown in the panel on the host as root, then approve it
  ("Let <host> join?") and choose its location, oversubscription and, for now, the Proxmox API token the panel will
  use. Enrollment keys are single-use and expire unless made otherwise, and can be revoked. The installation secret is
  rotated on every enrollment, so a leaked `anchor.toml` stops working. An Anchor can reach the panel at a URL other
  than `APP_URL`. The Anchors screen lists each agent with its endpoint, version and when it was last heard from.
- The scheduler polls every node each minute with a real Proxmox API call. An unreachable node says why on the card
  that lost its data (powered off, firewalled, untrusted TLS certificate and so on). A status the poll hasn't refreshed
  reads Unknown rather than Online, so a stopped scheduler or queue shows up. The same poll records each guest's power
  state.
- Nodes have overview, servers and settings pages. Settings can test the saved connection and fill in the host's
  sockets, cores, CPUs and memory from it. Network interfaces can be created, edited and deleted, and a topology view
  shows each node's bridges and the VLANs on them.
- The panel records which Proxmox cluster each node belongs to, identified by the cluster's CA fingerprint so two
  clusters with the same name stay apart. A host outside a cluster shows as standalone. VMIDs are checked for
  uniqueness per cluster.
- Servers follow guests that Proxmox moves between nodes through HA recovery or migration. The minute poll moves the
  server to its new node when the move is unambiguous and flags it for an administrator otherwise. Network changes are
  held while the flag stands. Before, a moved guest's server went stale without any sign: power commands seemed to
  work, usage stopped syncing and network changes went to the wrong host
  ([#161](https://github.com/ConvoyPanel/panel/issues/161), in part).

#### Servers

- Servers are built by importing a disk image instead of cloning a template (see Removed). An image definition holds
  the OS type, hardware settings and minimum resources. An image version is one build of its disks, and each server
  records the version it came from. Hardware settings are edited in a form generated from the node's own Proxmox API
  schema, so a key Proxmox won't accept is refused on save instead of at first boot. Images download through Proxmox's
  `download-url`, which verifies the checksum. A file with nowhere else to live can be uploaded to the panel, which
  serves it to the node over an expiring signed link.
- The ISO library is panel-wide. There is one entry per ISO, and a node fetches it the first time someone mounts it
  there.
- Servers can have more than one disk. Extra disks can be added at creation (`limits.disks[]`) or later from the admin
  server page's Disks tab, and can be grown or removed. Capacity is checked per storage across all of a server's disks.
- The create form asks only for name, hostname, owner, node, storage, image and password. Everything else sits in
  summaries that open for editing. A build can be saved as a preset and applied in one pick.
- The server area has Overview (with live CPU and memory trends), Resource usage (CPU, memory, disk and network on one
  time axis), Networking, Storage (every attached device, with boot switches and boot order), ISO library, Firewall,
  Backups, Security (SSH keys and root password) and Rebuild. Rebuild shows the install's progress step by step and
  offers Retry when it fails.
- The Firewall tab shows inbound and outbound chains, each ending in its default policy, with logging per direction,
  drag-to-reorder and an on/off switch per rule. The add-rule dialog asks for direction, allow or block, traffic, and
  source or destination, and offers the cluster's aliases, IP sets and groups by name. Setting inbound to Drop with no
  rule allowing SSH asks for confirmation. The packet log is parsed into time, verdict, addresses and port. Rules are
  read from and written to Proxmox, and the client API exposes the same.
- Consoles open from a split button that remembers the last type used. The display console has a keys bar, scaling
  options and clipboard in both directions. The serial terminal console needs a serial device, which new servers get
  and older ones can add from the console picker. A console that fails says why, such as the agent refusing, the agent
  being unreachable from the browser, or the guest having no display, and offers the fix where there is one.
- Client and admin server lists show whether each server is running, stopped or unknown. Power actions are tracked by
  their Proxmox task. A second command while one is running gets a 409 `power_action_in_progress`, and the UI shows one
  progress toast that survives a reload and ends in success or failure.
- Backups can be created from a dialog that asks for a name and keeps mode, compression and lock under Advanced. The
  quota sits in the backups view, and creating is disabled up front, with the reason, when the node has no
  backup-capable storage. A failed backup records why (storage exceeded, timeout or other). The owner sees the reason,
  and administrators also see Proxmox's own message.
- The server overview reads disk usage from the QEMU guest agent when it is running and shows the disk limit when it
  isn't.
- The admin server page shows live state, specification and details with copyable owner, VMID and UUID, and has power
  controls and a link to the client view of the same server. The admin servers list has bulk power actions.

#### Storage

- A node can have several storages, and ISOs and backups belong to a storage rather than a node. To register a
  storage, pick it from what the node reports. Proxmox supplies its name, capacity, content types and whether it is
  shared, and the operator adds a display name and reserved headroom.
- Capacity comes from Proxmox: live when the node answers, otherwise what the last poll recorded. The API says which
  in `capacitySource` and `observedAt`. Each storage shows physical use, what Convoy has allocated and what is
  untracked. Untracked space is not shown for thin-provisioned and deduplicating backends. The reserved headroom is
  enforced when servers are created, but an unreachable node doesn't block creation.
- Shared storage is one entry per cluster definition, linked to every node Proxmox says can reach it. A fleet-wide
  Storage screen lists each pool once with the nodes that reach it.
- Each storage has a page listing what fills it, largest first: servers, backups and ISOs. An administrator can delete
  a backup or ISO from there, or a server after typing its name.

#### Networking and IPAM

- IPAM organises addresses into address blocks (IPv4 or IPv6, by CIDR) inside address block groups, which replace
  address pools and are attached to nodes. Existing pools are migrated.
- Addresses are allocated in numeric order by a single locked query, so two servers created at once can't receive the
  same address. Large IPv4 blocks and all IPv6 blocks create addresses as they are needed, which removes the old
  10,000-address limit on IPv6.
- Each address is available, assigned or reserved. Network, broadcast and gateway addresses are reserved automatically
  and can't be released, and administrators can reserve any other. Blocks show utilisation. A block of up to 4,096
  addresses can be shown as a map, where cells are clicked or dragged to select addresses for bulk reserve, release or
  delete. Assigning an address starts from the server and offers the next free one.
- VLANs ([#150](https://github.com/ConvoyPanel/panel/issues/150)): a network interface can be VLAN-aware with a default
  tag, a server can override the tag, and each node keeps a list of declared VLANs that a server's tag must come from.

#### Accounts and sign-in

- Passkeys for signing in and confirming identity, named after the authenticator that created them, such as
  "1Password" or "YubiKey 5 Series".
- Two-factor authentication with an authenticator app, one sign-in challenge for whichever second factor an account
  has, and recovery codes, which passkey-only accounts get too.
- Sign-in with Google, GitHub, GitLab or any OpenID Connect provider (Keycloak, Authentik, Okta, Auth0, Azure AD and
  others), each enabled in configuration. Accounts link providers under Connected Accounts.
- Account pages have a profile with a cropped picture, an SSH keychain, personal API tokens (full access, servers read
  and write, or servers read-only), and the active sessions with a button to sign each one out. Deleting a user ends
  their sessions.

#### Administration

- A Users screen lists, searches, creates and edits accounts. An administrator can't demote or delete their own
  account. A user created without a password gets an invite link to set their own. The link is emailed when mail is
  configured and always shown to the administrator to copy. Issuing a new link voids the old one.
- Administrators can review an account's passkeys, SSH keys, API tokens, connected sign-in providers and two-factor,
  and revoke any of them. They can't add credentials for another user.
- A Settings screen holds the bandwidth defaults, mail, updates, and the account policy that sets what users may change
  about their own account, pictures included.
- A command palette, and admin navigation grouped by area.

#### API

- The Application API covers everything the admin panel does, including IPAM, storage, network interfaces and the
  overview. It serves the same routes under `/api/application` with a bearer token as `/api/admin` serves with a
  session.
- Application tokens are managed under Tokens in the admin area. They belong to the panel rather than the
  administrator who created them, so they survive that administrator's deletion, which is still recorded on the token.
  Each token carries abilities per resource (`servers:read`, `servers:write`, `servers:*` or `*`; write includes read)
  and can be restricted to networks. A token can never create or revoke tokens.
- Users can create personal access tokens for the client API. These tokens can't reach account settings.
- Errors come back as `{ "message": "…", "code": "…" }` with a stable code.
- Writes to a guest's Proxmox configuration carry Proxmox's config digest. A change made on the node in the meantime is
  refused with a 409 instead of being overwritten.

### Changed

- **BREAKING**: PostgreSQL 17 is the only supported database. A 4.x panel's MySQL data is converted during the cutover
  described above.
- The panel runs on PHP 8.4 and Laravel 12, and the frontend on React 19.
- The frontend is rewritten. It has a new sidebar that switches to a server's own pages when you open one, light and
  dark themes, and layouts that work on a phone.
- Bandwidth limits are a speed cap plus an overage penalty, which either throttles to a set rate or disconnects the
  network interface once the quota is used. Both resolve per server, then per node, then from the panel-wide default
  in Settings. Each server's quota resets on its own day of the month. Speed caps are sent to Proxmox in its decimal
  MB/s.
- **BREAKING**: A server's `status` is split into `lifecycle` (installing, ready, and so on), `suspendedAt` and
  `powerState`.
- **BREAKING**: Power commands moved from `PATCH /api/client/servers/{server}/state` with `{ "state": … }` to
  `POST /api/client/servers/{server}/power` with `{ "command": … }`. `GET …/state` is unchanged. The admin and
  application APIs have the same pair.
- **BREAKING**: `GET /api/client/servers/{server}/statistics` wraps its array in `data`, like its sibling endpoints.
- **BREAKING**: `POST /api/application/users/{user}/generate-sso-token` returns `link`, a single-use signed URL that
  expires after `sso.link_ttl`, instead of `token`. The `consume-token` route is gone, so billing modules that build
  the login URL themselves need updating. Every use is written to the audit log.
- **BREAKING**: `/api/application/address-pools` is replaced by address block groups.
- Cloud-init sets the root password on every OS, Windows included. The 4.x path that set it live through the QEMU guest
  agent is not carried over.
- Storage sizes are shown in IEC units (MiB, GiB).
- Page subtitles and descriptive card text are gone, and page actions sit on a toolbar row below the heading.

### Removed

- **BREAKING**: Templates. Servers are built from images, and template rows are not migrated, because a template is
  only a VMID on a node while an image needs the disks and their hardware profile. Existing servers keep running. Add
  an image for each OS you offer before customers order or rebuild.
- **BREAKING**: Coterm. Consoles go through Anchor. Nodes carried over from 4.x work for everything except the console
  until their host is enrolled with Anchor.
- **BREAKING**: Adding a node by hand, including `POST /api/application/nodes`. Nodes join through Anchor enrollment.

### Fixed

- Fixed every download with a checksum failing, ISO downloads included. Convoy sent `algorithm` where Proxmox expects
  `checksum-algorithm`, and Proxmox rejects parameters it doesn't know.
- Fixed network sync setting a server's firewall default policies back to ACCEPT on every address change, rebuild and
  sync. It now sets only whether the firewall and IP filter are on, and leaves the policies to the server's owner.
- Fixed a backup created with lock requested being saved unlocked. The request was validated as `is_locked` but read
  as `locked`.
- Fixed a disk resize during server build failing without anything noticing, leaving the guest at the image's disk
  size. Convoy now waits for the Proxmox task, and retries one that timed out.
- Fixed a worker killed mid-job leaving a server's job lock behind for good, so every later job of that kind for the
  server waited forever. Locks now expire, and a job that finds one held waits and retries.

### Security

- Creating or deleting API tokens and SSH keys, setting a server's root password and rebuilding a server now ask you
  to confirm your identity first. A confirmation lasts five minutes. A stolen session cookie or an unattended browser
  could otherwise mint a full-access token that outlives logout and a password change.
- Changing your password signs out every other session, replaces the remember-me token and emails the account owner.
- Passwords must be at least 12 characters and no longer than 72 bytes, with no composition rules, and are checked
  against known breaches through Have I Been Pwned's k-anonymity API. The check is skipped when the service can't be
  reached, so air-gapped panels still work. Passwords set by an administrator follow the same policy.
- Two-factor authentication counts as enabled only once a code from the authenticator has been entered. Accounts that
  already have a secret are carried over as confirmed and are still challenged at sign-in.
- Uploaded profile pictures are decoded, cropped, scaled to at most 512 px and re-encoded as WebP, so the bytes a user
  uploads are never served. Images over 50 megapixels are refused.
- Invite tokens are stored only as a hash, and an unknown, used or expired invite link gets the same answer.

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