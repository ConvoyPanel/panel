# sbx kits

Provisioning for running a coding agent in a [Docker Sandbox](https://docs.docker.com/ai/sandboxes/)
(`sbx`) against this repo. sbx has no auto-detection for repo-local kits, so a kit
is just a committed directory you reference explicitly with `--kit`.

## `dev/` — Convoy 4.x dev environment

Installs ddev and starts the stack inside the sandbox (its Docker daemon, DB, and
volumes are isolated from your host ddev). It also sets up headless browsing:
Playwright is pinned and installed to `/opt/sbx-e2e` (never the repo), and
`dev/browser.mjs` is published to `/opt/sbx-e2e/browser.mjs` for scripts to import.
Because `/etc/hosts` is a read-only mount in a sandbox — which otherwise makes
`ddev start` fail outright — a startup step overmounts a writable copy so ddev can
register `*.ddev.site`.

```sh
sbx run --kit .sbx/dev claude
```

The first run installs ddev and pulls its images (slow, once). To make later
starts instant, snapshot the provisioned sandbox into a template:

```sh
sbx template save convoy4x-dev
sbx run -t convoy4x-dev --kit .sbx/dev claude   # install is now a no-op; only `ddev start` runs
```

Then finish app provisioning inside the sandbox (see the kit's `agentContext`, or
`.sbx/dev/spec.yaml`):

```sh
ddev composer install
npm install
ddev artisan key:generate
ddev artisan migrate
ddev artisan db:seed --class=ServerSeeder
```

## Differences from the 5.x kit

The two branches run different stacks, so the kits are not interchangeable — the
project provisioning differs even though the sandbox plumbing is identical.

- MySQL 8.0 and PHP 8.2 here, Postgres 17 and PHP 8.4 on 5.x.
- The ddev project is `convoy4x`, so the app is at `https://convoy4x.ddev.site`.
  That one has to differ: a 4.x and a 5.x sandbox otherwise fight over the same
  ddev project name. The kit itself is still `convoy-dev` on both branches —
  kits are referenced by path (`--kit .sbx/dev`), so there is nothing to collide.
  The suggested *template* name is scoped, though, since the saved image bakes in
  this branch's PHP and database versions and would otherwise clobber 5.x's.
- No separate test database. `tests/Pest.php` uses `DatabaseTransactions` rather
  than `RefreshDatabase`, so the suite runs against the dev database and rolls its
  own writes back — seeded dev data is visible to it and will fail any test that
  asserts on a fleet-wide aggregate. `ddev artisan migrate:fresh` before a full run.
- Seeding is `ServerSeeder` (a location, a node, ten servers), and it needs no
  Proxmox credentials.

## Boundaries

- **Secrets** live in the gitignored `.env`, mounted into the sandbox — never in a
  kit.
- **Notifications** and any personal network setup come from global kits in the
  operator's own dotfiles, injected automatically by their `sbx` wrapper; `.sbx/dev`
  is project provisioning only. Multiple `--kit` refs compose, so they layer cleanly.
