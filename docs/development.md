# Development

Convoy runs locally on [ddev](https://ddev.readthedocs.io/) — Laravel 12, PHP 8.4,
Postgres 17, Node 22. ddev provisions the whole stack (web, database, redis) and
runs Horizon and the scheduler for you.

## Prerequisites

- Docker — [OrbStack](https://orbstack.dev/) (recommended on macOS) or Docker Desktop
- [ddev](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/)

## Local development (ddev)

### First-time setup

```sh
ddev start                              # boot web + Postgres + redis
ddev composer install
ddev exec php artisan key:generate      # only if APP_KEY is empty
ddev npm install
ddev exec php artisan migrate
```

The app is served at **https://convoy.ddev.site**. ddev supplies the database,
redis, and mail config (see `web_environment` in `.ddev/config.yaml`), so you
don't set DB credentials by hand.

### Frontend / Vite

```sh
ddev npm run dev              # Vite dev server with HMR
ddev npm run build           # production build
ddev npm run types:generate  # regenerate TS types (typescript:transform + wayfinder)
ddev npm run tc              # tsc type-check (no emit)
```

### Background workers

Horizon (queues) and the scheduler run automatically as ddev web daemons — no
separate terminals needed. They're defined under `web_extra_daemons` in
`.ddev/config.yaml`.

### Tests

The suite uses `RefreshDatabase` against a **separate `db_test` Postgres
database** (auto-created by a `post-start` hook), so running tests never touches
your dev data.

```sh
ddev exec php artisan test
ddev composer analyze        # PHPStan static analysis
```

### Handy commands

```sh
ddev exec php artisan <cmd>          # any artisan command
ddev ssh                             # shell inside the web container
ddev logs -s web                     # web/php/nginx logs
ddev snapshot                        # save current DB state
ddev snapshot restore <name>         # restore it (e.g. jump back to a stable baseline)
ddev restart | ddev poweroff
```

Tip: use `ddev snapshot` to save a stable database before churning it on a
feature branch, then `ddev snapshot restore` when you switch to a bug fix.

## Sandboxed development (Docker Sandboxes / `sbx`)

To run a coding agent (or a disposable throwaway environment) against Convoy
**without touching your machine**, use [Docker Sandboxes](https://docs.docker.com/ai/sandboxes/).
Each sandbox is a Linux microVM with its *own* Docker daemon, database, and
volumes — fully isolated from your host ddev, so a mistake only costs you an
`sbx rm`.

### The provisioning kit

`.sbx/dev/` is a committed kit that installs ddev and boots the stack inside the
sandbox (see [`.sbx/README.md`](../.sbx/README.md)):

```sh
sbx run --kit .sbx/dev claude        # any agent works in place of `claude`
```

The first run installs ddev and pulls its images (slow once). Snapshot the
provisioned sandbox into a template so later starts are instant:

```sh
sbx template save <sandbox-name> convoy-dev
sbx run -t convoy-dev --kit .sbx/dev claude   # install becomes a no-op; only `ddev start` runs
```

### Finish provisioning (inside the sandbox)

```sh
ddev composer install
ddev exec php artisan migrate
ddev exec php artisan test
```

### Testing against a real Proxmox node

Nodes (and their Proxmox credentials) normally live in the database, created
through the UI. For a scripted dev/test node, `DevNodeSeeder` builds one from
environment variables in your **gitignored `.env`**:

```dotenv
PROXMOX_FQDN=10.0.0.10
PROXMOX_TOKEN_ID=root@pam!convoy
PROXMOX_TOKEN_SECRET=xxxxxxxx
# PROXMOX_PORT=8006
# PROXMOX_VERIFY_TLS=false
```

```sh
ddev exec php artisan db:seed --class=DevNodeSeeder
```

Because `.env` rides the mounted workspace, it's available in the sandbox with no
extra wiring. **Use a scoped Proxmox API token, not full `root@pam`** — the
sandbox protects your machine, not your hypervisor, and an autonomous agent with
that token can control it.

### Notes

- Secrets stay in the gitignored `.env` (mounted in), never in the committed kit.
- The sandbox's ddev/database is isolated from your host ddev (separate Docker
  daemon) — no volume conflicts.
- `vendor/` and `node_modules/` live in the mounted workspace, so installs inside
  the sandbox also land in your host checkout.
