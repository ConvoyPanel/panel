# Changelog

This file is a running track of new features and fixes to each version of the panel released starting with `v2.0.0`.

This project follows [Semantic Versioning](http://semver.org) guidelines.

## v4.2.3

- Updated code for applying rate limits to NIC. Convoy will no longer override settings other than ratelimit, NIC
  model (e.g., e1000, vmxnet3, virtio, etc.), and firewall status.

### Changes

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