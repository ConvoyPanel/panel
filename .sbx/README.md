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

## `tailscale/` — join the sandbox to your tailnet

Installs Tailscale and joins the sandbox to your tailnet so work inside can reach
tailnet resources (e.g. the Proxmox node the dev seeder talks to). It composes with
the dev kit — `--kit` refs layer:

```sh
sbx run --kit .sbx/tailscale --kit .sbx/dev claude
```

> **Order matters.** Kit `startup` commands run sequentially in `--kit` order, and
> the dev kit's `ddev start -y` can block for minutes on a first-run image pull.
> List `.sbx/tailscale` **first** so the tailnet comes up promptly instead of
> waiting behind ddev. (With a saved `convoy-dev` template, `ddev start` is fast and
> order barely matters.)

Like ddev, the first run installs Tailscale (slow, once). Snapshot it into a template
so later starts skip the install:

```sh
sbx template save convoy-dev
sbx run -t convoy-dev --kit .sbx/tailscale --kit .sbx/dev claude
```

### Supplying the auth key (never committed)

The kit is generic and secret-free. On startup it resolves an auth key in this order:

1. `TS_AUTHKEY` in the environment;
2. a gitignored `.sbx/tailscale.env` in the workspace (`cp .sbx/tailscale.env.example
   .sbx/tailscale.env` and paste a key — see that file for the recommended key type);
3. neither → the node isn't logged in; run `sudo tailscale up --accept-routes --ssh`
   inside the sandbox and open the printed URL in your host browser.

**Optional — fetch the key from 1Password automatically (private, not in this repo).**
Because the repo is public, the `op` lookup lives in your dotfiles, mirroring the
notify-kit wrapper (`_sbx_build_kit`). A minimal version: at launch, read the key on
the host and write a throwaway mixin that only sets the env var, then add it as an
extra `--kit`:

```sh
sbx-ts() {
  local d="${TMPDIR:-/tmp}/sbx-ts-kit"; mkdir -p "$d"
  cat > "$d/spec.yaml" <<EOF
schemaVersion: "1"
kind: mixin
name: tailscale-authkey
environment:
  variables:
    TS_AUTHKEY: "$(op read 'op://Private/tailscale-sbx/authkey')"
EOF
  sbx run --kit .sbx/tailscale --kit .sbx/dev --kit "$d" "$@"
}
```

The committed `.sbx/tailscale` kit consumes `$TS_AUTHKEY` either way, so this stays
entirely on your machine.

### Networking mode & egress

**TUN mode (the normal case).** The sandbox usually doesn't create `/dev/net/tun` at
boot, but it *does* grant `NET_ADMIN` and ship the kernel tun driver — so the kit
creates the device node and functionally probes it, then runs `tailscaled` in TUN
mode. Traffic to tailnet IPs routes transparently through the `tailscale0` interface
(`ip route get <100.x>` → `dev tailscale0`), so apps — including ddev's nested
containers — reach tailnet hosts by IP with no proxy. Only if the tun driver genuinely
can't produce an interface does it fall back to **userspace-networking**, where
outbound access is via a local proxy (`socks5h://localhost:1055` /
`http://localhost:1055`).

**Use IPs, not names.** MagicDNS is disabled (`--accept-dns=false`) because the
sandbox's `/etc/resolv.conf` is read-only and tailscaled can't manage it. Reach hosts
by their `100.x` tailnet IP; set `PROXMOX_FQDN` to the node's tailnet IP.

**DERP relay, not direct.** `sbx` blocks raw UDP egress, so WireGuard can't form a
direct peer tunnel — traffic relays over DERP (HTTPS/443). Still end-to-end encrypted;
adds ~tens of ms. Fine for API/control traffic, slower for bulk transfers. Confirm
with `tailscale ping <ip>` (shows `via DERP(region)`). This is why the kit's egress
allowlist covers Tailscale's control plane and DERP relays (`*.tailscale.com`) — it
keeps the handshake on 443. A very locked-down `sbx policy` may still need widening;
check `tailscale status` first.

## Boundaries

- **Secrets** (e.g. `PROXMOX_*`) live in the gitignored `.env`, mounted into the
  sandbox — never in this kit. The tailnet auth key follows the same rule: it comes
  from the environment or the gitignored `.sbx/tailscale.env`, never committed.
- **Notifications** are handled by a separate, global kit (in dotfiles) that the
  `sbx` wrapper injects automatically; this kit is project provisioning only.
  Multiple `--kit` refs compose, so they layer cleanly.
