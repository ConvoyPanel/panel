# Changelog
This file is a running track of new features and fixes to each version of the panel released starting with `v2.0.0`.

This project follows [Semantic Versioning](http://semver.org) guidelines.

## v2.0.1-beta
### Fixed
* Problem where validation errors for the SSH key wouldn't show up
* Bug where user couldn't unset a SSH key after saving one

## v2.0.0-beta (Bombay)
### Added
* Storing of CPU, memory, disk, snapshots, backups, and bandwidth limits
* Added server suspensions
* Added real-time status updates of server installs (though it will be deprecated in v3.x.x)
* Automatic bandwidth throttler when a user exceeds the bandwidth limit

### Changed
* Internally, server details are now passed around the application using Laravel Data by Spatie. Though in v3.x.x, we are planning on switching to Data Transfer Objects by Spatie. We pulled the wrong package and didn't realize until one month in using the package LOL.
* Virtual machines are now limited to one disk. Multiple disks may be supported when a daemon is available in the future.
* The built-in web server is now Caddy instead of Nginx. This provides auto SSL out of the box.

### Fixed
* The commands in the node viewing page for installing the VNC Broker and templates.

### Known Bugs
* The real-time server installation communication is known to be buggy and will be resolved in v3.x.x
* Editing the server field for IP Addresses will sometime result in the first server of the node to be used. This will be resolved in v3.x.x

![The Bombay cat breed is the mascot for v2](https://imgur.com/fP6oxn9.png)
