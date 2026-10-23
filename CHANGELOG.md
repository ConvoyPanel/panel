# Changelog

This file is a running track of new features and fixes to each version of the panel released starting with `v2.0.0`.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
follows [Semantic Versioning](https://semver.org) guidelines.

## v4.7.0-rc.4

Everything in v4.6.2, the security release cut from v4.6.1:

### Security

- PHP no longer announces its exact version in an `X-Powered-By` header on every response, and Caddy no longer sends
  its `Server` header. Anyone could read which PHP a panel ran and pick out the ones still carrying a known
  vulnerability.
- Caddy, the one container that faces the internet, is upgraded from 2.6.4 to 2.11.7. The `caddy:2.6-alpine` tag the
  image was built from stopped moving in February 2023, so panels have been terminating TLS with a web server that
  missed every security fix since, including the HTTP/2 Rapid Reset one.
- The Caddy container now mounts only `public/`, read-only, instead of the whole panel directory. It never needed
  `.env`, which holds the application key and database password, and a bug in the web server should not be able to
  reach them.
- Horizon and the scheduler now run as `www-data`, the user PHP-FPM already serves the panel as, instead of root. A
  queue job is a PHP object handed over through Redis, so anyone who reaches Redis can have Horizon run code, and
  until now that code ran as root with the whole panel directory mounted read-write: it could rewrite the panel and
  `.env`. The php and workers containers now put `storage/` and `bootstrap/cache` under `www-data` each time they
  start, so an existing install needs nothing done by hand, and the install guide's `chmod -R o+w storage/*
  bootstrap/cache` is no longer needed.

### Changed

- MySQL 8.4, the long-term support release, replaces 8.0, which stopped getting security fixes in April 2026. MySQL
  upgrades an 8.0 data directory in place the first time 8.4 starts, and there is no way back, so back up before
  updating, as the update guide already says.
- Redis 8 replaces 7.0, which is end of life. It reads 7.0's data files as they are.
- The php, workers and workspace images are built on an exact PHP version, 8.2.35, which fixes the PHP2Shell remote
  code execution bugs (CVE-2026-109618, CVE-2026-109619). They used to be built on whichever PHP 8.2 Docker had already
  downloaded, so a panel installed in September stayed on 8.2.34 through every update until its base image was pulled
  by hand. A PHP security release now becomes a Convoy patch release that pins the fixed version.
- The redis extension is installed with PIE, from Packagist and GitHub, instead of from PECL. PECL was unreachable for
  most of 21 October, which left panels unable to rebuild their images on the day the PHP fix came out, and the PHP
  project has deprecated it.
- The workspace image is built on Debian bookworm. Debian 11 (bullseye) left support in August 2026 and its security
  packages have since been removed from the mirror, so the old image no longer builds at all: `docker compose up -d
  --build` fails with "404 Not Found" for `sudo` and `unzip` on every fresh install and update. The PHP project also no
  longer publishes 8.2 images for bullseye.

### Fixed

- Fixed rebuilding the workspace image failing, seemingly at random, with "404 Not Found" for packages such as `sudo`
  and `unzip` from the Debian mirror. The Dockerfile ran `apt-get update` and `apt-get install` as two steps, so a
  rebuild could reuse a package index cached months earlier and ask the mirror for versions it had since replaced. They
  now run as one step. (Since bullseye's packages were removed from the mirror, the same error happens on every build;
  moving to bookworm is what fixes that.)
- Fixed `docker compose up -d --build` reinstalling every PHP extension whenever the extension installer published a
  new release, which was most weeks. The installer was fetched as `latest`, which Docker re-downloads on every build,
  and a new copy threw away every cached layer after it. That is how a rebuild could fail on a day nothing in Convoy
  had changed, such as when PECL was down. The installer and PIE are now pinned to exact versions, so a rebuild does
  the same thing every time.

## v4.7.0-rc.3

### Added

- A server whose installation failed can now be retried. The "Install failed" screen was a dead end that only said to
  contact an administrator; it now offers the reinstall form, and the API accepts a reinstall for a server in that
  state. Every other blocked state, such as suspended, still refuses it.
- The "Installing", "Restoring Backup" and "Restoring Snapshot" screens now update by themselves. They check the
  server every few seconds and move on once the work is done, with a spinning icon to show they're live; before, they
  stayed put until the page was refreshed.

### Changed

- Deleting a server and keeping its virtual machine are now separate actions. **Delete** always removes everything --
  backups (including locked ones), the virtual machine on the node, and the server's entry in Convoy -- and when the
  virtual machine is already gone from the node, it simply removes the Convoy entry. **Disconnect**, a new button next
  to it, replaces the "Do not purge VM and related files" checkbox: it removes the server from Convoy and leaves the
  virtual machine and its backups on the node. The API is unchanged; `DELETE` with `no_purge` still disconnects.

### Fixed

- Fixed reinstalling a server while its node couldn't be reached being taken as "there's no VM": the reinstall then
  skipped removing the old VM and failed later on its taken VMID. It now fails straight away with the node's error, so
  it can just be retried.
- Fixed every error from Proxmox filling the log with three copies of the same stack trace -- about 256 lines per error,
  every few seconds while a page polled a server whose VM was gone. An error is now logged once, with one trace, and its
  message is Proxmox's own ("Proxmox returned 500: Configuration file ... does not exist").
- Fixed reinstalling a server whose VM no longer exists failing every time. Reinstall stopped and deleted the old VM
  first, and Proxmox answers both with "does not exist" when a previous install died before or during the clone. It
  now skips straight to building when there is no VM.
- Fixed a failed reinstall of an already-failed server staying on "Installing" forever instead of going back to
  "Install failed". The failure handler wrote through the copy of the server captured when the reinstall started, and
  when that copy already read "install_failed" the write was skipped as a no-op.
- Fixed deleting a server failing, over and over, when its virtual machine was already gone from the node or it had a
  locked backup. A failed delete can now also be retried, or the server disconnected, straight from the "Failed to
  Delete" screen, and a server stuck on "Deleting" can be disconnected.
- Fixed a delete treating any error from Proxmox as "the virtual machine is gone". Only Proxmox's own "does not exist"
  counts now, so a node that is briefly unreachable can no longer get a server removed from Convoy while its virtual
  machine keeps running.
- Fixed a server's jobs getting permanently stuck after one slow request to Proxmox. The locks that keep two jobs of the
  same kind from running on a server at once never expired, so a job the queue killed for running past its time limit
  left its lock behind for good: every later job of that kind for the server -- including the force-stop that reinstall
  and delete start with -- failed instantly until the lock was cleared from Redis by hand. Locks now expire shortly
  after their job's time limit, a job that finds one held waits and retries, and the power and password jobs' time
  limits no longer fall below a single Proxmox request.
- Fixed failing jobs retrying with no pause. A job whose request to Proxmox failed was retried at once, again and
  again, until it ran out of time -- thousands of requests a minute against a node that was already struggling. Jobs
  now wait a few seconds, then longer, between retries.
- Fixed opening a server that has been deleted showing "Something went wrong" with "No query results for model
  [Convoy\Models\Server]". It now shows "Server not found" with a way back to the server list, and the API names a
  missing record in plain words ("This server could not be found. It may have been deleted."). Deleted nodes and users
  in the admin area, which used to break the page outright, now get the same screen.


## v4.7.0-rc.2

### Fixed

- Fixed the Create Server modal offering no templates once a node was picked. The node select holds its id as a string,
  and since v4.7.0-rc.1 the template group lookup skips anything that is not a positive integer -- which was meant to
  stop requests going out before a node is chosen, but also stopped them after. The id is now converted before the
  lookup.

## v4.7.0-rc.1

### Changed

- The admin overview's Attention card now names what needs attention instead of reporting a bare count, and every row
  links to the record itself -- a failed server to its admin page, a failed backup to its server's backups tab. Groups
  holding several records open a sheet listing them, capped at the 25 most recent with a note saying how many more
  there are. Previously the card showed only a failed-server count while its caption mixed in failed backups and
  servers mid-delete, so it could read "0" with backups broken, and its link went to the unfiltered server list either
  way. Suspended and mid-delete servers are deliberately left off it: neither is a failure, and both are already
  counted under Server State.

### Fixed

- Fixed a disk resize during server build failing without anything noticing, leaving the guest at its template's disk
  size rather than the one the plan asks for. Proxmox answers a resize by forking a worker and returning its task ID,
  which Convoy discarded, so the build reported success whatever became of the resize. It now waits for the task, and
  retries one that failed with a timeout -- Proxmox runs `qemu-img` under a fixed 10 second limit, which a resize
  issued right after a full clone can exceed on slower storage.
- Fixed modals taller than the browser window clipping their own header and submit row with no way to scroll to them.
  The panel is now bounded by the viewport and its body scrolls within it, so Create Server and Create Node stay usable
  at shorter window heights.
- Fixed opening a modal leaving keyboard focus behind it, which sent the first few Tab presses through the page
  underneath instead of the dialog.
- Fixed the create-server form requesting addresses and template groups before a node is chosen, producing a handful of
  404s every time the modal opened.
- Fixed modals going out without an accessible name, so a screen reader announced only "dialog" instead of reading the
  heading already on screen.

## v4.6.2

### Security

- PHP no longer announces its exact version in an `X-Powered-By` header on every response, and Caddy no longer sends
  its `Server` header. Anyone could read which PHP a panel ran and pick out the ones still carrying a known
  vulnerability.
- Caddy, the one container that faces the internet, is upgraded from 2.6.4 to 2.11.7. The `caddy:2.6-alpine` tag the
  image was built from stopped moving in February 2023, so panels have been terminating TLS with a web server that
  missed every security fix since, including the HTTP/2 Rapid Reset one.
- The Caddy container now mounts only `public/`, read-only, instead of the whole panel directory. It never needed
  `.env`, which holds the application key and database password, and a bug in the web server should not be able to
  reach them.
- Horizon and the scheduler now run as `www-data`, the user PHP-FPM already serves the panel as, instead of root. A
  queue job is a PHP object handed over through Redis, so anyone who reaches Redis can have Horizon run code, and
  until now that code ran as root with the whole panel directory mounted read-write: it could rewrite the panel and
  `.env`. The php and workers containers now put `storage/` and `bootstrap/cache` under `www-data` each time they
  start, so an existing install needs nothing done by hand, and the install guide's `chmod -R o+w storage/*
  bootstrap/cache` is no longer needed.

### Changed

- MySQL 8.4, the long-term support release, replaces 8.0, which stopped getting security fixes in April 2026. MySQL
  upgrades an 8.0 data directory in place the first time 8.4 starts, and there is no way back, so back up before
  updating, as the update guide already says.
- Redis 8 replaces 7.0, which is end of life. It reads 7.0's data files as they are.

- The php, workers and workspace images are built on an exact PHP version, 8.2.35, which fixes the PHP2Shell remote
  code execution bugs (CVE-2026-109618, CVE-2026-109619). They used to be built on whichever PHP 8.2 Docker had already
  downloaded, so a panel installed in September stayed on 8.2.34 through every update until its base image was pulled
  by hand. A PHP security release now becomes a Convoy patch release that pins the fixed version.
- The redis extension is installed with PIE, from Packagist and GitHub, instead of from PECL. PECL was unreachable for
  most of 21 October, which left panels unable to rebuild their images on the day the PHP fix came out, and the PHP
  project has deprecated it.
- The workspace image is built on Debian bookworm. Debian 11 (bullseye) left support in August 2026 and its security
  packages have since been removed from the mirror, so the old image no longer builds at all: `docker compose up -d
  --build` fails with "404 Not Found" for `sudo` and `unzip` on every fresh install and update. The PHP project also no
  longer publishes 8.2 images for bullseye.

### Fixed

- Fixed rebuilding the workspace image failing, seemingly at random, with "404 Not Found" for packages such as `sudo`
  and `unzip` from the Debian mirror. The Dockerfile ran `apt-get update` and `apt-get install` as two steps, so a
  rebuild could reuse a package index cached months earlier and ask the mirror for versions it had since replaced. They
  now run as one step. (Since bullseye's packages were removed from the mirror, the same error happens on every build;
  moving to bookworm is what fixes that.)
- Fixed `docker compose up -d --build` reinstalling every PHP extension whenever the extension installer published a
  new release, which was most weeks. The installer was fetched as `latest`, which Docker re-downloads on every build,
  and a new copy threw away every cached layer after it. That is how a rebuild could fail on a day nothing in Convoy
  had changed, such as when PECL was down. The installer and PIE are now pinned to exact versions, so a rebuild does
  the same thing every time.

## v4.6.1

### Security

- Fixed the client API resolving `{iso}` on its own rather than through the server it is nested under, which let a mount
  request name an ISO belonging to a node the caller has no server on. No practical exposure followed from it: an ISO is
  addressed by UUID, and the only place Convoy shows a non-administrator an ISO's UUID is the media list for a server on
  that ISO's own node, where mounting it is already allowed. Hidden ISOs were refused either way. The fix restores the
  scoped route-model binding that the rest of the client API relies on to keep one server's URL from reaching another
  server's resources.

### Fixed

- Fixed servers with no bandwidth limit being incorrectly rate limited to 1 MB/s ([#157](https://github.com/ConvoyPanel/panel/issues/157)).

## v4.6.1-rc.1

### Changes

- Fixed servers with no bandwidth limit being incorrectly rate limited to 1 MB/s ([#157](https://github.com/ConvoyPanel/panel/issues/157)).

## v4.6.0

### Changes

- Published the database port to the loopback interface in `docker-compose.yml` so you can access your database locally without exposing it to the network.
- Convoy will now skip Proxmox configuration tasks when the desired state already matches what's on the node, reducing unnecessary API calls.
- Increased `UpdatePasswordJob` retry attempts to 15 with a 30 second backoff to better survive slow disk resize operations during VM creation.
- Improved bulk IP address range validation to show a descriptive error when the ending address is less than the starting address or the range exceeds 65,536 addresses.

## v4.6.0-beta

### Changes

- Published the database port to the loopback interface in `docker-compose.yml` so you can access your database locally without exposing it to the network.
- Convoy will now skip Proxmox configuration tasks when the desired state already matches what's on the node, reducing unnecessary API calls.
- Increased `UpdatePasswordJob` retry attempts to 15 with a 30 second backoff to better survive slow disk resize operations during VM creation.
- Improved bulk IP address range validation to show a descriptive error when the ending address is less than the starting address or the range exceeds 65,536 addresses.

## v4.5.1

### TIME SENSITIVE SECURITY UPDATE

Please update immediately to this version to ensure your Convoy installation is secure.

### Changes

- Updated vulnerable dependencies
- Additional details are embargoed until a later date

## v4.5.0

### TIME SENSITIVE SECURITY UPDATE

Please update immediately to this version to ensure your Convoy installation is secure.

### Changes

- Updated to Laravel 11 from 10

## v4.5.0-rc.1

### TIME SENSITIVE SECURITY UPDATE

Please update immediately to this version to ensure your Convoy installation is secure.

### Changes

- Updated to Laravel 11 from 10

## v4.4.1

### TIME SENSITIVE SECURITY UPDATE

Please update immediately to this version to ensure your Convoy installation is secure. Details are still pending.


## v4.4.0

### Changes

- For security purposes, I disabled publication of database, Redis, and workspace ports in the `docker-compose.yml` file.

## v4.3.1

### Changes

- Tokens will be revoked when an administrator privileges are removed #132

#### From v4.3.0-rc.1

- Added guest agent support for changing Windows user passwords #120
    - This feature is still experimental. Please provide feedback on
      our [Discord community](https://discord.convoypanel.com/) and report bugs on
      our [GitHub
      repository](https://github.com/ConvoyPanel/panel/issues).
- Servers will now automatically start after unsuspension #119
- Fixed parsing of user realm types #126
- Fixed broken redirect when unauthenticated while accessing certain admin routes #123
- Fixed fetching of nameservers when there are none present #125

## v4.3.0-rc.1

> [!IMPORTANT]
> The source between v4 and v10 will begin to diverge starting here. Once v10 is complete, v4's commit history will be
> abandoned. We will not be introducing any changes to the database structure in v4 to prevent any conflicts with v10's
> database structure.

### Changes

- Added guest agent support for changing Windows user passwords #120
- Servers will now automatically start after unsuspension #119
- Fixed parsing of user realm types #126
- Fixed broken redirect when unauthenticated while accessing certain admin routes #123
- Fixed fetching of nameservers when there are none present #125

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
