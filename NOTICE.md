# Third-party notices

Convoy itself is proprietary and licensed under the terms in `LICENSE.md`. The
container image published from this repository additionally contains the
third-party components below, each under its own license. Those licenses govern
those components only: they are aggregated with Convoy on the same image, not
combined with it, and nothing here changes the license of Convoy's own code.

## serversideup/php

The image's base layers are `serversideup/php`, which provides the PHP runtime
configuration, the container entrypoint, the s6-overlay process supervision and
the Caddy/FrankenPHP web server configuration.

- Project: <https://github.com/serversideup/docker-php>
- License: GPL-3.0-or-later
- Versions used: see the `PHP_CLI_IMAGE` and `PHP_RUNTIME_IMAGE` arguments at the
  top of `Dockerfile`, which pin both the release tag and the content digest.

These components are redistributed **unmodified**. Complete corresponding source
for the exact version in any image we publish is available at the project URL
above, at the release tag recorded in the `Dockerfile`. Requests for source may
also be sent to the address in `LICENSE.md`.

Convoy adds files alongside these components (`docker/entrypoint.d/`) rather than
editing them. If that ever changes, the modified files must be published under
GPL-3.0-or-later — keep customisations in our own files.

## Other components

The image also contains, from the upstream layers it is built on:

| Component | License |
| --- | --- |
| PHP | PHP License v3.01 |
| FrankenPHP | MIT |
| Caddy | Apache-2.0 |
| s6-overlay | ISC |
| Alpine Linux base system and packages | various — run `apk info --license -a` in the image for the per-package list |
| musl libc | MIT |
| Composer dependencies | see `composer.lock` |
| npm dependencies (compiled into `public/build`) | see `package-lock.json` |

The services referenced by `compose.yml` — PostgreSQL (PostgreSQL License) and
Redis (AGPLv3, or RSALv2/SSPLv1 at your option) — are pulled directly from their
own publishers at run time and are not redistributed as part of Convoy's image.
