# Changelog

This file is a running track of new features and fixes to each version of the panel released starting with `v2.0.0`.

This project follows [Semantic Versioning](http://semver.org) guidelines.

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

-   Settings layout for client side server settings

### Fixed

-   possibility that `address_ids` will cause an exception when it's null when creating a new server

## v3.4.0-beta

### Changed

-   Refactored routes

### Added

-   Navigation Bar Context. Now switching pages are even more seamless

## v3.3.0-beta

### Fixed

-   Lack of cancel button when deleting an API key

### Added

-   Ability for administrators to impersonate the client view for a server and also visit the server's configuration in the admin area.
-   Warnings when creating a new node to disable privilege separation and grant root permissions.

## v3.2.0-beta

### Fixed

-   Broken network syncing when updating an address's assigned server
-   Text alignment for server counter on the users table in the admin area

### Added

-   Hyperlinks to the owner on the servers table

## v3.1.2-beta

### Fixed

-   Scoped routing bindings in RouteServiceProvider that were breaking some routes

## v3.1.1-beta

### Fixed

-   Conflicting named routes that would break route optimization/caching

## v3.1.0-beta

### Fixed

-   IP Address updating
-   Server build updating

### Removed

-   Option to sync or not sync network settings when deleting an IP address
    -   The default behavior is always to sync

## v3.0.0-beta (Tuxedo)

### Fixed

-   Server installs
-   a bunch of other stuff

### Changed

-   from proprietary hard-coded api tokens to Bearer tokens for the application/external api
-   the whole entire frontend

### Added

-   Server hostnames
-   Node location grouping

### Notes

-   This release is so big that I can't really summarize everything

![The maxwell cat meme is the mascot for v3](https://imgur.com/mowvogE.png)

## v2.0.3-beta

### Fixed

-   Inability to delete IP address from the admin user interface

## v2.0.2-beta

### Fixed

-   FQDN validator for the hostname field when adding a new node

## v2.0.1-beta

### Fixed

-   Problem where validation errors for the SSH key wouldn't show up
-   Bug where user couldn't unset a SSH key after saving one

## v2.0.0-beta (Bombay)

### Added

-   Storing of CPU, memory, disk, snapshots, backups, and bandwidth limits
-   Added server suspensions
-   Added real-time status updates of server installs (though it will be deprecated in v3.x.x)
-   Automatic bandwidth throttler when a user exceeds the bandwidth limit

### Changed

-   Internally, server details are now passed around the application using Laravel Data by Spatie. Though in v3.x.x, we are planning on switching to Data Transfer Objects by Spatie. We pulled the wrong package and didn't realize until one month in using the package LOL.
-   Virtual machines are now limited to one disk. Multiple disks may be supported when a daemon is available in the future.
-   The built-in web server is now Caddy instead of Nginx. This provides auto SSL out of the box.

### Fixed

-   The commands in the node viewing page for installing the VNC Broker and templates.

### Known Bugs

-   The real-time server installation communication is known to be buggy and will be resolved in v3.x.x
-   Editing the server field for IP Addresses will sometime result in the first server of the node to be used. This will be resolved in v3.x.x

![The Bombay cat breed is the mascot for v2](https://imgur.com/fP6oxn9.png)
