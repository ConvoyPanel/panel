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

Run an agent against Convoy in a throwaway Linux microVM — its own Docker daemon,
database, and volumes, isolated from your host ddev. A mistake costs only `sbx rm`.
One committed kit (full details in [`.sbx/README.md`](../.sbx/README.md)):
`.sbx/dev` boots the ddev stack.

### 1. Start it

```sh
sbx run --kit .sbx/dev claude
```

Any agent works in place of `claude`. The first run installs ddev (slow once);
make later starts instant by saving a template:

```sh
sbx template save <sandbox-name> convoy-dev
sbx run -t convoy-dev --kit .sbx/dev claude   # install is now a no-op
```

### 2. Finish setup (run inside the sandbox)

```sh
ddev composer install
ddev exec php artisan migrate
ddev exec php artisan test
```

### 3. (Optional) Seed a Proxmox node

Add to your **gitignored `.env`**, then seed:

```dotenv
PROXMOX_FQDN=10.0.0.10          # an address the sandbox can reach
PROXMOX_TOKEN_ID=root@pam!convoy
PROXMOX_TOKEN_SECRET=xxxxxxxx
# PROXMOX_PORT=8006
# PROXMOX_VERIFY_TLS=false
```

```sh
ddev exec php artisan db:seed --class=DevNodeSeeder
```

`.env` is mounted in, so it's already there. **Use a scoped API token, not full
`root@pam`** — the sandbox protects your machine, not your hypervisor.

### Reaching the Proxmox node

The sandbox has no route to a private node on its own. If yours isn't publicly
reachable, arrange network access on the host side before `sbx run`, and set
`PROXMOX_FQDN` to an address the sandbox can actually reach.

### Notes

- Secrets stay in the gitignored `.env` (mounted in), never in the committed kit.
- The sandbox's ddev/database is isolated from your host ddev (separate Docker
  daemon) — no volume conflicts.
- `vendor/` and `node_modules/` live in the mounted workspace, so installs inside
  the sandbox also land in your host checkout.
