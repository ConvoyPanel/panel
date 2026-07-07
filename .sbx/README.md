# sbx kits

Provisioning for running a coding agent in a [Docker Sandbox](https://docs.docker.com/ai/sandboxes/)
(`sbx`) against this repo. sbx has no auto-detection for repo-local kits, so a kit
is just a committed directory you reference explicitly with `--kit`.

## `dev/` — Convoy dev environment

Installs ddev and starts the stack inside the sandbox (its Docker daemon, DB, and
volumes are isolated from your host ddev).

```sh
sbx run --kit .sbx/dev claude
```

The first run installs ddev and pulls its images (slow, once). To make later
starts instant, snapshot the provisioned sandbox into a template:

```sh
sbx template save convoy-dev
sbx run -t convoy-dev --kit .sbx/dev claude   # install is now a no-op; only `ddev start` runs
```

Then finish app provisioning inside the sandbox (see the kit's `agentContext`, or
`.sbx/dev/spec.yaml`):

```sh
ddev composer install
ddev exec php artisan migrate
ddev exec php artisan db:seed --class=DevNodeSeeder   # needs PROXMOX_* in .env
```

## Boundaries

- **Secrets** (e.g. `PROXMOX_*`) live in the gitignored `.env`, mounted into the
  sandbox — never in this kit.
- **Notifications** are handled by a separate, global kit (in dotfiles) that the
  `sbx` wrapper injects automatically; this kit is project provisioning only.
  Multiple `--kit` refs compose, so they layer cleanly.
