# AGENTS.md

## Generated frontend artifacts

These are NOT committed (matches the existing `routeTree.gen.ts` convention):

- `resources/scripts/routeTree.gen.ts` — TanStack Router file-based route tree
- `resources/scripts/wayfinder/` — Wayfinder typed route helpers
- `resources/scripts/types/generated.d.ts` — Spatie typescript-transformer output (DTOs + enums)
- `resources/scripts/types/typescript-transformer-manifest.json`

Regenerate with `npm run types:generate` (also runs automatically via `predev` / `prebuild`). CI should run the generators before typecheck/build; a clean-tree assertion afterwards catches anything that drifted.

## Running PHP, Composer, and Artisan

There is no host-side `php` or `composer`. They live in the `app` Docker Compose service.

- **One-off command (no live containers needed):**
  `docker compose run --rm --no-deps app <command>`
  Example: `docker compose run --rm --no-deps app composer install`
- **Against running stack:** `docker compose exec app <command>`
- **Composer write commands** (`install`, `require`, `update`): pass `--no-scripts` when Redis/MySQL aren't up, because `post-autoload-dump` runs `php artisan package:discover` which bootstraps the framework and connects to cache. Run `php artisan package:discover` manually once the stack is healthy.

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
5. Do not call the Proxmox API; these docs are reference-only.