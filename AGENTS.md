# AGENTS.md

## Generated frontend artifacts

These are NOT committed (matches the existing `routeTree.gen.ts` convention):

- `resources/scripts/routeTree.gen.ts` — TanStack Router file-based route tree
- `resources/scripts/wayfinder/` — Wayfinder typed route helpers
- `resources/scripts/types/generated.d.ts` — Spatie typescript-transformer output (DTOs + enums)
- `resources/scripts/types/typescript-transformer-manifest.json`

Regenerate with `ddev npm run types:generate` (also runs automatically via `predev` / `prebuild`). CI should run the generators before typecheck/build; a clean-tree assertion afterwards catches anything that drifted.

## Frontend design consistency

The UI must read as **one app**, not a patchwork of per-page styles. Before building any
screen, find the closest existing one and reuse its structure, spacing, and components —
do not invent new paddings, gaps, type scales, or bespoke card layouts.

- **Reuse components, not one-offs.** Build on `components/ui/*` (`Card`, `Table`,
  `Progress`, `Typography`, …). Match the established grid rhythm (`gap-2` / `gap-4`) and the
  standard `Card` padding — don't hand-roll different padding per section.
- **Favor data density.** No large card wrapping a single small number. Pack related stats
  into one card as a definition list (`<dl>` with `<dt className='text-xs text-muted-foreground'>`
  / `<dd>`), the way `Client/Server/Overview/SpecificationsCard.tsx` does. A screen full of
  near-empty cards is a smell.
- **Format consistently.** Bytes go through `byte-size` (`byteSize(n, { units: 'iec' })`), the
  same as the rest of the app — not an ad-hoc formatter.

When in doubt, mirror an existing page verbatim rather than introducing a new pattern.

## Frontend data layer

Don't hand-roll what the wrappers already do. Per `features/<domain>/api.ts`:

- **Fetch** via `apiFetch` + a Wayfinder route object — never raw `axios`/`fetch` or hardcoded URLs.
  Reads are `queryOptions` + a `useX` hook (`@tanstack/react-query`).
- **Mutate** with `useMutation`; update the cache with `useQueryMutator`, surface server errors with
  `handleFormErrors(e, form.setError)`. Don't call `apiFetch` straight from a click handler.
- **Forms** are react-hook-form + `zodResolver`, with the `zod` schema exported from `api.ts`. Use the
  `Form` field wrappers (`InputForm`, `SelectForm`, `CheckboxForm`, …) and `FormButton` — not bare
  `Input`/`Select` + `useState`.
- **Clipboard** goes through the `useClipboard` hook, not `navigator.clipboard` directly.

Reference: `features/locations`, `features/template-groups`. Admin controllers are served under both
`/api/admin` and `/api/application`, so Wayfinder emits URI-keyed dicts — reference the admin URI
explicitly (see `features/tokens/api.ts`).

## Local development (ddev)

Local dev runs on [ddev](https://ddev.com) with **Postgres 17**. One-time setup:

```bash
ddev start                        # web + postgres + redis + horizon + scheduler
ddev composer install
ddev artisan migrate              # or: ddev artisan migrate:fresh
ddev npm install && ddev npm run build
```

The app is served at https://convoy.ddev.site. For frontend HMR, run `ddev npm run dev`
(Vite is served at https://convoy.ddev.site:3000).

Roll back the database while iterating on migrations (replaces the old Makefile snapshot hack):

```bash
ddev snapshot --name pre-migration
ddev snapshot restore pre-migration
```

## Running PHP, Composer, Artisan, and npm

There is no host-side `php` / `composer` / `node`; they run inside the ddev web container:

- `ddev artisan <command>` — Artisan
- `ddev composer <command>` — Composer (the stack is up during `ddev start`, so
  `post-autoload-dump`'s `package:discover` connects to cache/DB fine)
- `ddev npm <command>` — npm runs in-container (so `types:generate` can call
  `php artisan` directly)
- `ddev ssh` — open a shell in the web container

DB / Redis / mail are configured via `web_environment` in `.ddev/config.yaml`, whose values
override `.env` (Laravel's Dotenv does not overwrite real env vars). `ext-gmp` is added
via `webimage_extra_packages`.

## Docker sandbox

When developing inside an isolated **Docker Sandbox** (an AI agent's disposable VM), see
[docs/docker-sandbox.md](docs/docker-sandbox.md). In that environment you are free to install and
run whatever tooling you need to develop and test (it's throwaway and isolated — don't commit those
installs). It also documents the sandbox-local fix for `php artisan tinker` segfaulting
(PsySH `usePcntl` fork crash) and the intermittent heavy-command SIGSEGVs (retry). These are
sandbox-only notes — nothing there belongs in committed project config or CI.

## Laravel style

Prefer current, namespaced support APIs over legacy Laravel 5 helper aliases: use `Arr::get`
/ `Str::slug`, not `array_get` / `str_slug`. Current framework helpers such as `auth()`,
`config()`, `now()`, `filled()`, and `data_get()` are fine; use `$request->user()` when a
request is already available.

## Proxmox VE API documentation

Generated Proxmox VE API docs are in:

- `docs/pve-api/llms.txt` - compact overview
- `docs/pve-api/search-index.json` - endpoint search index
- `docs/pve-api/endpoints.json` - normalized full endpoint data
- `docs/pve-api/markdown/endpoints/` - one Markdown page per endpoint
- `docs/pve-api/llms-full.txt` - full concatenated docs, use only when needed

When answering Proxmox VE API questions:

1. Read `docs/pve-api/llms.txt` first.
2. Use `search-index.json` or `endpoints.json` to find relevant endpoints.
3. Open the specific endpoint Markdown file for details.
4. Do not guess endpoint names from memory.

## Proxmox data DTOs

Model Proxmox data to *our* domain, not Proxmox's wire format. Don't mirror
their property-list layout or their terse/unclear key names (`ssd`, `secret`,
`ro`, `di`) 1:1 — rename to clear domain properties (`isEmulatingSSD`,
`tokenSecret`, `isReadonly`) and lean on PHP features JSON lacks, especially
**backed enums** for closed value sets instead of raw strings/ints. The mapping
back to Proxmox's keys/format is the codec's job (`App\Extensions\Spatie\Data\
Proxmox` — `#[ProxmoxProperty]`, casts, `PropertyList`), so keep conversion
logic there and reusable, not re-implemented per DTO (e.g. byte-unit scaling).
5. Do not call the Proxmox API; these docs are reference-only.

## Live End-to-End Testing

The Proxmox credentials live in the project **`.env`** (`PROXMOX_FQDN`, `PROXMOX_TOKEN_ID`,
`PROXMOX_TOKEN_SECRET`, and usually `PROXMOX_NODE_NAME` / `PROXMOX_SSH_TARGET`). **They are read by
Laravel's Dotenv (`env()` / `config()`), NOT exported into the container shell** — so
`ddev exec sh -c 'echo $PROXMOX_FQDN'` prints nothing even when they are set. Do **not** conclude
from an empty `echo` that they are missing. To check, read the file directly (`grep -E '^PROXMOX_'
.env`) or ask Laravel (`ddev artisan tinker --execute="echo config('...')"`). In practice, assume
they are defined and just run the seeder — it warns and no-ops if they truly are not.

When these credentials are present, **seed a live node so you can test against real data** instead
of stubbing the network:

```bash
ddev artisan db:seed --class=DevNodeSeeder          # a real Proxmox node from the env vars
```

`DevNodeSeeder` is idempotent (skips itself when the creds are unset or the node already exists),
so it is safe to run on every fresh sandbox / after `migrate:fresh`. Optional knobs: `PROXMOX_PORT`,
`PROXMOX_VERIFY_TLS`, `PROXMOX_NODE_NAME` (see the seeder's docblock).

With a node seeded, provision servers to exercise the client/admin UI end-to-end (this clones real
VMs on the node, so it needs the live node above):

```bash
# SEED_SERVER_USER is an email or user id; SEED_SERVER_COUNT defaults to 10.
ddev exec sh -c 'SEED_SERVER_USER=you@example.com SEED_SERVER_COUNT=3 php artisan db:seed --class=ServerSeeder'
```

This is the preferred way to browser-verify frontend work (log in, drive the real screens with a
Playwright/CDP harness) — reach for isolated dev-routes with stubbed responses only when no live
node is available.

The user may also specify an optional corresponding `$PROXMOX_SSH_TARGET` variable for the
`$PROXMOX_FQDN` in the environment.
If set, you may `ssh $PROXMOX_SSH_TARGET` into the Proxmox node for enhanced testing
(e.g., for cases where using the Proxmox API isn't sufficient).
