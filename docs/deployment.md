# Deployment

Convoy is deployed as a container image. The supported install is a dedicated
host running Docker, with the panel, its queue worker, its scheduler and —
unless you bring your own — Postgres and Redis on it.

Like VirtFusion and SolusVM, Convoy expects to be the only application on the
host. It binds ports 80 and 443 and assumes it owns them.

## Install

```bash
curl -fsSL https://install.convoypanel.com | sudo bash
```

The installer asks for the hostname customers will reach the panel on and an
email address for the first administrator, installs Docker if it is missing,
writes `/opt/convoy`, starts the stack, and prints a URL and a temporary
password.

Non-interactively:

```bash
curl -fsSL https://install.convoypanel.com | sudo bash -s -- \
  --domain panel.example.com --email you@example.com --yes
```

### Requirements

- Debian 12/13, Ubuntu 22.04/24.04, AlmaLinux 9/10 or Rocky Linux 9/10
- 2 GB RAM minimum, 4 GB recommended
- 20 GB disk
- Ports 80 and 443 free and reachable
- `x86_64` or `arm64`

### TLS

If the hostname you give the installer is a domain that resolves to the host, a
certificate is obtained automatically over ACME and renewed without further
action. Certificates are stored in a volume, so they survive upgrades.

If you give it an IP address, the panel serves a self-signed certificate and
browsers will warn on first visit — certificate authorities do not issue for
bare IPs. Point a domain at the host and re-run with `--domain` to fix this.

To terminate TLS somewhere else instead, set `CONVOY_AUTO_HTTPS=off` and put your
proxy in front, then set `TRUSTED_PROXIES` to its address (see
[configuration.md](configuration.md#trusted-proxies)) — otherwise every client
appears to Convoy as the proxy.

## Day-to-day

`convoy` wraps the underlying `docker compose` commands:

```
convoy ps                 status of every container
convoy logs web           follow the panel's logs
convoy upgrade            back up, pull new images, restart, verify
convoy backup             dump the database to /opt/convoy/backups
convoy artisan <cmd>      run an Artisan command
convoy shell              open a shell in the panel container
convoy horizon            queue worker status
convoy support            send logs and diagnostics to the Convoy team
```

Nothing here is magic — if you know Docker, `cd /opt/convoy` and use
`docker compose` directly.

## Upgrading

```bash
convoy upgrade
```

This pulls the new images and replaces the `convoy` command with the new
release's copy. It updates `compose.yml` too, unless you've edited it: then the
new one is saved next to it as `compose.yml.new`. It stops the scheduler, lets
the worker finish its current jobs and takes a database backup. Then it starts
the web container, which runs the migrations, waits for the panel to report
healthy, and starts the worker and scheduler again.

A 5.x install upgrades the same way: 5.x's `convoy upgrade` swaps itself for
this release's command and hands over to it.

To control exactly which version you run, pin `CONVOY_VERSION` in
`/opt/convoy/.env` to a release tag instead of `latest`, and change it when you
want to move.

## What is actually running

| Service | What it does |
| --- | --- |
| `web` | Serves the panel. FrankenPHP (Caddy + PHP in one process), which also terminates TLS. |
| `worker` | Horizon. Every Proxmox action is a queued job, so nothing works without this. |
| `scheduler` | `schedule:work`. Runs the entries in `routes/console.php`, including the per-minute node and Anchor liveness polls that drive the status indicators. |
| `postgres` | Bundled database. |
| `redis` | Bundled Redis, used for the queue, cache and sessions. |

The first three are the same image with different commands. All application
configuration lives in `/opt/convoy/.env` and is read by all three.

Convoy is a control plane: almost every request spends its time waiting on the
Proxmox API rather than on PHP. The web tier is therefore configured for
predictability rather than raw throughput, and running more `web` replicas is
rarely the answer to a slow panel — check the hypervisors first.

## Using your own database and Redis

Point `DB_*` and `REDIS_*` at your own hosts in `/opt/convoy/.env`, then edit
`/opt/convoy/compose.yml` and delete:

- the `postgres` and `redis` services,
- the `depends_on:` block from `web`, `worker` and `scheduler`,
- the `postgres:` and `redis:` entries under `volumes:`.

Then apply it:

```bash
cd /opt/convoy
docker compose up -d --remove-orphans
```

The image is identical either way. Your edits to `compose.yml` survive upgrades.
When a release changes the file, `convoy upgrade` leaves yours alone and saves
the new one as `compose.yml.new` for you to compare.

Note that `convoy backup` only handles the bundled database; with an external
one, back it up with your provider's tooling.

## Backups

`convoy backup` writes a gzipped `pg_dump` to `/opt/convoy/backups`. It is
run automatically before every upgrade. It is *not* run on a schedule — put it
in cron if you want that:

```
0 3 * * * /usr/local/bin/convoy backup >/dev/null
```

Volumes hold the database, Redis data, issued certificates and the contents of
`storage/`. `docker compose down` leaves all of them in place; `down -v` destroys
them.

## Troubleshooting

**The panel container will not start.** Its startup checks fail loudly and on
purpose. `convoy logs web` will name the problem — a missing `APP_KEY`, an
`APP_URL` still pointing at localhost, or a storage volume the container cannot
write to.

**Everything looks fine but nothing happens when I act on a server.** The worker
is down. `convoy horizon` tells you; `convoy logs worker` tells you why.

**Status indicators are stale.** The scheduler is down. `convoy logs
scheduler` should show `nodes:poll` and `anchors:poll` running each minute.

**Certificate errors.** ACME needs port 80 reachable from the internet and a
domain that resolves to this host. `convoy logs web` includes Caddy's output,
which says precisely which of those failed.
