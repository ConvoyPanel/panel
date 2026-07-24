# Working in the Docker Sandbox

This repo is often developed inside a **Docker Sandbox** — an isolated, disposable VM (its own
kernel, not just a container) that an AI agent drives. These notes are **sandbox-specific**: they
describe environment quirks and freedoms that do **not** apply to a normal host or CI, so nothing
here should be baked into committed project config.

## You may install and run whatever you need

The sandbox is isolated and throwaway, so inside it you are **free to install and run any tooling**
needed to develop and test — system packages (`sudo apt-get …`), global npm/pip/uv packages,
browsers and drivers (e.g. Playwright + Chromium for visual checks), profilers, etc. You do **not**
need to ask before installing dev/test dependencies here, and such installs are **local to the
sandbox** — do not commit them to the repo (no new runtime deps in `composer.json` / `package.json`
just to satisfy a one-off local probe).

## Keep sandbox web traffic off your **host's** dev services

The sandbox reaches the network through an HTTP(S) proxy
(`HTTPS_PROXY=http://gateway.docker.internal:3128`). HTTP clients — `curl`, and crucially
**Playwright/Chromium** — hand the *hostname* to that proxy instead of consulting `/etc/hosts`,
and the proxy resolves it **on the host side**. So a request to `https://convoy.ddev.site` from
inside the sandbox does **not** hit the sandbox's own ddev on `127.0.0.1` — it lands on **your
host's** ddev and drives your real app. Symptoms this produced: headless-Chromium e2e logins
showing up as `Chrome on Linux` sessions in the *host* DB, `test@test.com`'s password appearing
to "change" (the tests reset it on your DB), and create/delete-node tests mutating real data —
all while the sandbox's own DB stayed empty (`select count(*) from session_records` = 0). Confirm
which app answers with `curl -sk https://convoy.ddev.site/ -o /dev/null -w '%{remote_ip}\n'`:
`127.0.0.1` is the sandbox; anything else is the proxy → your host.

Defense in depth — the leak should be blocked at all three layers so no single miss re-opens it:

1. **Proxy-bypass local dev TLDs** so the hostname resolves to the sandbox's own loopback.
   Append to `/etc/sandbox-persistent.sh` (sandbox-local, sourced before every command, never
   committed — do **not** put this in `.ddev/config.yaml`, which is shared with the host):

   ```bash
   if [ -z "${SBX_DDEV_NOPROXY_DONE:-}" ]; then
     export NO_PROXY="${NO_PROXY:+$NO_PROXY,}.ddev.site,ddev.site"
     export no_proxy="$NO_PROXY"
     export SBX_DDEV_NOPROXY_DONE=1
   fi
   ```

   Wiped on sandbox **recreate**, so re-apply it at the start of a fresh sandbox (verify with the
   `remote_ip` curl above).

2. **Deny `*.ddev.site` at the proxy** so that even if the bypass is missing (fresh sandbox) or a
   tool ignores `NO_PROXY`, a proxied request to your host's ddev is *blocked* rather than
   silently forwarded. Run from the **host** (the allow-side syntax is
   `sbx policy allow network <domain>`; confirm the deny subcommand with `sbx policy --help`):

   ```bash
   sbx policy deny network '*.ddev.site'
   ```

   Bypass **+** deny means the only reachable `*.ddev.site` is the sandbox's own loopback — the
   deny is the backstop for the window before layer 1 is applied on a new sandbox.

3. **Guard the e2e scripts.** Don't hand-roll this per session — the `dev` kit ships
   `.sbx/dev/browser.mjs` and publishes it to `/opt/sbx-e2e/browser.mjs` on every start. It bypasses
   the proxy for ddev hosts and refuses to launch unless the app answers from `127.0.0.1`:

   ```js
   import { BASE, launch, newContext, login, capture } from '/opt/sbx-e2e/browser.mjs'

   const browser = await launch()          // proxy-bypassed + preflighted
   const ctx = await newContext(browser)   // ignores the mkcert cert
   const page = await login(ctx, { email: '…', password: '…' })
   const overflow = await capture(ctx, { url: '/admin/nodes', width: 768, path: '/tmp/nodes.png' })
   await browser.close()
   ```

   A test that can tell it's pointed at the host and stops is the last line of defense.

Playwright itself lives in `/opt/sbx-e2e`, **not** the repo, and scripts reach it by importing the
helper's absolute path (Node resolves `playwright` by walking up from `/opt/sbx-e2e`). Never
`npm install playwright` in the project — that puts sandbox-only tooling in `package.json` and
`package-lock.json`. The version is pinned in the kit because the Chromium build id is tied to it;
a floating `playwright@latest` installed mid-session gives you `Executable doesn't exist at
…/chromium-<id>`, and the fix is to use the pinned copy, not to re-download browsers.

## `ddev start` fails: "Failed to add hosts entry … read-only file system"

`/etc/hosts` is a read-only bind mount from the host, and `*.ddev.site` has no DNS answer reachable
from in here — so ddev's hostname step can neither resolve `convoy.ddev.site` nor add it, and
`ddev start` aborts. With no local app running it is very tempting to tunnel to the **host's** ddev
instead. Don't: that is exactly the leak the section above is about, dressed up as a fix.

The `dev` kit handles it on every start by overmounting a writable copy, after which ddev registers
its own hostnames normally:

```bash
tmp=$(mktemp); { cat /etc/hosts; echo '# sbx: writable hosts overmount'; } > "$tmp"
sudo install -m 0644 -o root -g root "$tmp" /var/lib/sbx-hosts
sudo mount --bind /var/lib/sbx-hosts /etc/hosts
```

Nothing is written to the workspace and the overmount dies with the sandbox, so this stays
sandbox-local. In a sandbox that predates the kit change, run it by hand, then `ddev start` and
confirm with the `remote_ip` curl above.

## `php artisan tinker` segfaults (SIGSEGV / exit 139) — fix

**Symptom:** `php artisan tinker` (especially the interactive REPL) intermittently dies with a
segfault instead of evaluating.

**Why it happens:** Tinker is built on **PsySH**, which — when `ext-pcntl` is present (it is here) —
defaults to `usePcntl = true` and runs *each* evaluation inside a **forked child process** (its
"forking loop"), so a fatal in your code can't kill the session and per-eval timeouts can be
enforced. The crash is **not** `pcntl_fork()` failing (a tight loop of bare `pcntl_fork()` runs
cleanly here). It is that after the fork the child re-enters PHP's runtime — opcache/JIT-compiled
code, loaded-extension state, the readline/terminal handle — inside the sandbox's virtualized
kernel, and *that* fork-and-continue occasionally faults. It's the **same class of intermittent
SIGSEGV the sandbox shows for other fork/thread-heavy tools** (PHPStan's parallel workers, the
Vite/esbuild build) — an environment interaction with forking, **not** a Convoy or Laravel bug.

**Fix (sandbox-local, uncommitted):** tell PsySH to evaluate in-process instead of forking, by
dropping a config in the web container's home. It is read from `~/.config/psysh/config.php`
automatically (no env var, no repo change), and it lives outside the project tree so it is never
committed:

```bash
ddev exec bash -c 'mkdir -p ~/.config/psysh && cat > ~/.config/psysh/config.php <<PHP
<?php return ["usePcntl" => false];
PHP'
```

Re-create it after a `ddev` container rebuild (the container home is ephemeral). **Do not** add this
to `.ddev/config.yaml`, a committed `.psysh.php`, or any other repo file — it is a sandbox
work-around, and real dev machines / CI want the default forking behaviour.

**Tradeoff:** with `usePcntl = false` a fatal error in a tinker command ends the REPL session (no
forked isolation) and there is no per-eval timeout. Fine for scripted probes; mildly less forgiving
for long interactive sessions.

**Quoting caveat (bites agents constantly):** most "tinker is broken" moments in this repo are
actually **shell-quoting** errors in a `ddev exec … --execute="…"` one-liner (nested quotes /
backslashes producing malformed PHP → a PHP parse error or exit 1, *not* a segfault). Prefer piping
a heredoc into `php artisan tinker`, or `--execute` with a payload free of nested quotes, and read
the error: a `ParseError` is your quoting, exit `139` is the real segfault.

## Other intermittent SIGSEGVs — just retry

The same environment instability sporadically hits `ddev npm run build`, `cache:clear`, and
PHPStan's parallel workers (across both PHP and Node, so it's not any one tool). There is **nothing
to fix in the repo** — forcing e.g. PHPStan serial in `phpstan.neon` would only penalise real CI for
a sandbox quirk. Mitigations:

- **Re-run** the command; it usually succeeds within a couple of tries.
- Run PHPStan with `--debug` (serial) to dodge the parallel-worker crash:
  `ddev exec ./vendor/bin/phpstan analyse --memory-limit=4G --debug`.
- Run the test suite as `ddev exec vendor/bin/pest` (or `ddev exec php artisan test`), **not**
  `ddev artisan test` — the ddev global-command wrapper segfaults booting the runner here.

A different sandbox session may not hit any of this at all.
