# AGENTS.md

## Generated frontend artifacts

These are NOT committed (matches the existing `routeTree.gen.ts` convention):

- `resources/scripts/routeTree.gen.ts` — TanStack Router file-based route tree
- `resources/scripts/wayfinder/` — Wayfinder typed route helpers
- `resources/scripts/types/generated.d.ts` — Spatie typescript-transformer output (DTOs + enums)
- `resources/scripts/types/typescript-transformer-manifest.json`

Regenerate with `ddev npm run types:generate` (also runs automatically via `predev` / `prebuild`). CI should run the generators before typecheck/build; a clean-tree assertion afterwards catches anything that drifted.

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
- `ddev npm <command>` — npm runs in-container (this is why `types:generate` calls
  `php artisan` directly rather than shelling through docker compose)
- `ddev ssh` — open a shell in the web container

DB / Redis / mail are configured via `web_environment` in `.ddev/config.yaml`, whose values
override `.env.next` (Laravel's Dotenv does not overwrite real env vars). `ext-gmp` is added
via `webimage_extra_packages`.

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