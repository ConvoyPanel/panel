# Frontend feature-slice migration — handoff

## STATUS: COMPLETE (2026-07-06)

All domain screens are sliced. `components/interfaces/` is gone. Decisions taken
(the "Open questions" below, resolved):

- **Routes:** left in `routes/` as thin adapters (option a). Not moved.
- **Shared home:** kept `lib/transformers/` + `types/` for cross-domain shared
  code; only **domain-private** types/transforms moved into slices. No
  `features/_shared/` was created — nothing needed a new home.
- **Barrels:** none. Components import deep paths; `api.ts` stays the data entry.
- **Admin/Client split:** one slice, subfoldered `components/{admin,client}/`
  (servers, overview). Other domains preserved their existing subfolders
  (ipam `AddressBlock/`, nodes `Create/Network/Storages`, servers `Create/pickers`).

Commits (one per domain; servers split into 2): account/security, auth,
locations, template-groups, ipam, nodes, servers (components; then types+xforms),
overview/dashboard.

What stayed shared (consumed by ≥2 domains, left in `types/` + `lib/transformers/`):
`server` (+ `ServerResources`), `address` / `address-block` / `address-block-group`
(mutually coupled, address pair used by servers/detail), `node`,
`network-interface`, `template` / `template-group`, `user`, `admin/user`.

What moved in-slice: `passkey`+`keychain`→account; `user` transform→auth;
`location`→locations; `storage` type+transform→nodes; `deployment`+`backup`
types and `server-resources`+`backup` transforms→servers; all domain components +
colocated modal-store/time-range hooks.

**users** has no component screens of its own (the only user component,
`UserPicker`, belongs to servers), and `types/admin/user` is shared, so users
remains data-layer only — nothing to slice.

Cross-slice component reuse that is intentional (imported via public slice
paths, not a boundary smell): nodes→`locations/LocationPicker`,
servers-rebuild→`template-groups` template components, ipam→a `servers` picker,
overview/dashboard→a `servers` card.

Every commit is green (`npm run tc` + `npx vite build`). Original migration plan
below, for reference.

---


Goal: evolve `resources/scripts/` from **layer-first** folders (`components/`, `types/`, `routes/`,
`lib/transformers/`) toward **feature-first slices** under `features/<domain>/`, so that everything
belonging to one domain lives in one folder. This is the "extend slices past `api.ts`" bet that the
Phase-4 api-layer migration deliberately left open.

Written 2026-07-06, right after the api-layer migration finished (all data-fetching consolidated into
`features/<domain>/api.ts` on `apiFetch` + Wayfinder; transformers moved to `lib/transformers/`; the
old `resources/scripts/api/` tree is gone).

## Where we are now (starting point)

`features/` today is **data-layer only** — each domain folder holds just `api.ts`:

```
features/
  account/{authenticator,passkeys,password}/api.ts
  auth/api.ts, auth/identity/api.ts
  ipam/api.ts, ipam/blocks/api.ts, ipam/blocks/addresses/api.ts
  nodes/api.ts, nodes/{storages,network-interfaces}/api.ts
  locations/api.ts  overview/api.ts  users/api.ts
  servers/api.ts (client list), servers/{admin,detail,state,backups}/api.ts
  template-groups/api.ts, template-groups/templates/api.ts
```

Everything else for those domains is still layer-first and scattered:

| Layer | Lives in | Movable? |
|---|---|---|
| Data (queries/mutations) | `features/<domain>/api.ts` | ✅ already sliced |
| Domain components | `components/interfaces/{Admin,Client,Auth}/<Domain>/**` (347 files total) | ✅ move into slice |
| Domain hooks / modal stores | co-located next to those components (e.g. `use-block-group-modal-store.ts`) | ✅ move with them |
| Hand-written types | `types/<domain>.ts` (19 files) | ✅ mostly movable |
| Transformers | `lib/transformers/<domain>.ts` | ⚠️ some shared (see below) |
| Routes | `routes/**` (48 files, TanStack **file-based**) | ⛔ constrained — see "Routes" |
| Generated types | `types/generated.d.ts` (+ manifest) — Spatie typescript-transformer output | ⛔ stays; backend-generated |
| Design-system primitives | `components/ui/**`, `components/layouts/**` | ⛔ stays; not domain-owned |

Path alias: `@/* → resources/scripts/*` (tsconfig `paths`, mirrored in vite). No `@/features` alias
needed; `@/features/...` already resolves.

## Target shape (per slice)

```
features/ipam/
  api.ts            # queries/mutations/fetchers  (exists)
  types.ts          # AddressBlockGroup, AddressBlock, Address, Paginated*  (from types/*)
  transforms.ts     # rawDataToAddressBlockGroup, ...  (domain-private ones only)
  components/        # CreateBlockGroupModal, AddressBlockTab, ServerPicker, ... (from components/interfaces/Admin/Ipam)
  hooks/            # use-block-group-modal-store, use-address-modal, ...
  index.ts?         # OPTIONAL barrel — decide (see Open questions)
```

Route files stay in `routes/` but become **thin** — they only import from the slice.

## The three real constraints (decide these before starting)

1. **Routes can't move.** TanStack Router here is **file-based** (`routes/` tree → generated
   `routeTree.gen`). A route's location *is* its URL, so route files must stay under `routes/`. Options:
   (a) leave routes in `routes/` as thin wrappers that import the slice's components/loaders
   (recommended, minimal risk); (b) switch affected routes to code-based/virtual routes to host them
   in-slice (bigger, router-wide change). Recommend (a) — treat `routes/` as a thin adapter layer, not
   part of the slice.

2. **Shared transformers/types.** Four transformers are used by **multiple** domains and have no single
   owner: `server` (ipam + servers/admin + servers/detail), `address` (all ipam modules + servers/detail),
   `template` + `template-group` (template-groups + servers/detail). Same story for some `types/*`
   (`server.ts`, `address.ts`). Slicing these naively creates feature→feature imports. Decide a home for
   cross-domain shared code up front — recommend a `features/_shared/` (transformers + types) so the
   coupling is explicit, and only move **domain-private** transformers/types into their slice. Keep
   `lib/transformers/` OR fold it into `features/_shared/transformers/` — pick one and be consistent.

3. **`components/ui` and `components/layouts` are not domain-owned.** Buttons, dialogs, tables, nav,
   avatars = design system. They stay put. Only `components/interfaces/**` (domain screens) moves.

## Domain inventory & suggested order

Do smallest/most-self-contained first to shake out the conventions, then the big shared ones.

| # | Domain | api (done) | components/interfaces | types | transformers | notes |
|---|---|---|---|---|---|---|
| 1 | account/security | `account/{authenticator,passkeys,password}` | `Client/Security/**` | `passkey`, `keychain` | `passkey` (1:1) | good pilot — self-contained, few shared deps |
| 2 | auth | `auth`, `auth/identity` | `Auth/**` | `user` | `user` (1:1) | login/passkey/identity screens |
| 3 | locations | `locations` | `Admin/Location/**` | `location` | `location` was dead (deleted) | tiny |
| 4 | templates | `template-groups`, `.../templates` | `Admin/Template/**` | `template`, `template-group` | `template*` (SHARED w/ servers) | first shared-transformer case |
| 5 | ipam | `ipam`, `ipam/blocks`, `.../addresses` | `Admin/Ipam/**` (incl. modal stores) | `address*`, `address-block*` | `address*` SHARED, `address-block*` ipam-only | modal-store hooks colocate |
| 6 | nodes | `nodes`, `nodes/{storages,network-interfaces}` | `Admin/Node/**` | `node`, `storage`, `network-interface` | mostly 1:1 | LoadBalancerSidebar optimistic update |
| 7 | users | `users` | (admin users screens) | `admin/user` | `admin/user` (1:1) | |
| 8 | servers | `servers`, `servers/{admin,detail,state,backups}` | `Admin/Server/**` + `Client/Server/**` | `server`, `server-resources`, `deployment` | `server` SHARED | biggest; do LAST. Note existing `serverQueries` split (`['server',uuid]` in detail vs `['servers']` in list) — keep distinct |
| 9 | overview / dashboard | `overview` | `{Admin,Client}/Dashboard/**` | — | — | cross-domain aggregation; keep light |

## Procedure per domain (repeat, one commit each)

1. `git mv` the domain's `components/interfaces/<Area>/<Domain>/**` → `features/<domain>/components/`
   (preserves history). Move colocated hooks/stores → `features/<domain>/hooks/`.
2. Move **domain-private** `types/<domain>.ts` → `features/<domain>/types.ts`; move domain-private
   transformers → `features/<domain>/transforms.ts`. Leave shared ones in `features/_shared/` (or
   wherever step-0 decided).
3. Rewrite imports. Bulk path rewrites: `grep -rl <old-path> | xargs perl -pi -e 's{old}{new}g'`
   (that's how the transformer move was done — pipe through `xargs`, don't pass a newline blob as argv).
4. Point the thin `routes/**` files at the new component locations.
5. Verify: `npx tsc --noEmit --project ./` then `npx vite build`. `public/build` is gitignored, so
   nothing to restore. Commit per domain: `refactor(frontend): slice <domain> into features/`.

## Open questions (decide once, apply everywhere)

- **Barrels?** Add `features/<domain>/index.ts` re-exporting the public surface, or import deep paths
  (`@/features/ipam/components/...`)? Barrels are tidier for consumers but can hurt tree-shaking / create
  cycles. Recommend **no barrels for components**, keep `api.ts` as the data entry point.
- **Shared home name:** `features/_shared/`? `features/common/`? Or keep `lib/transformers/` + `types/`
  for shared and only slice components? Pick one.
- **Admin vs Client split:** several domains have both an `Admin/<Domain>` and `Client/<Domain>` screen
  set (servers, dashboard). One slice with `components/{admin,client}/` subfolders, or two slices? Recommend
  one slice, subfoldered — mirrors how `features/servers/{admin,detail}` already split the api.
- **Scope discipline:** this is multi-day and touches ~350 component files. Strongly consider
  **convert-as-you-touch** for components (slice a domain only when you're already working in it) rather
  than a big-bang move, since components churn far more than the api layer did. The api-layer big-bang was
  tractable because it was ~90 small files with a mechanical pattern; components are larger and less
  uniform.

## Guardrails / gotchas

- Every domain must stay **green** (tsc + build) and be its own commit — never leave the tree broken
  across a stopping point (the api migration held to this).
- Watch for **feature→feature imports** creeping in; if slice A needs slice B's component/type/transform,
  that's a signal it belongs in `_shared/` (or the boundary is wrong).
- The `serverQueries` name collision (`features/servers/api.ts` list vs `features/servers/detail/api.ts`)
  is already resolved by keeping them in distinct modules — don't merge them while slicing.
- Generated `types/generated.d.ts` (`App.Data.*`) is produced by the backend Spatie transformer; never
  hand-edit or relocate it — slices consume `App.Data.*` in place.
- `components/ui`, `components/layouts`, `providers/`, `stores/`, `wayfinder/` are cross-cutting — leave them.

## Prior art in-repo

The Phase-4 api-layer migration (`docs/phase4-features-migration-handoff.md`) is the model for cadence,
verification, and commit granularity. Reference slices for the data layer:
`features/{overview,servers,ipam}/api.ts`.
