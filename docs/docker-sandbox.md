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
