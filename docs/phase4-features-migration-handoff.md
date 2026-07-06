# Phase 4 — `api/` → `features/` migration handoff

Working handoff for the frontend data-layer migration (Phase 4 of the v10 `next` rewrite).
Paused mid-IPAM on 2026-07-06. Read this before resuming so a cold start doesn't re-derive it.

## The pattern (what "migrate a domain" means)

The SWR shim is long gone. The old `api/**/{get*,use-*,create/update/delete}.ts` hooks already
use TanStack `queryOptions` directly. Migrating a domain means consolidating all its files into a
single `features/<domain>/api.ts` module that:

- uses `apiFetch` + Wayfinder **route objects** (`@/lib/api`) instead of raw `axios.get('/url')`,
- keeps the same `queryOptions` factory, convenience hooks, callable fetchers, and mutations,
- preserves every transform (`rawDataTo*`), key-mapping (camel→snake), and Zod schema verbatim,
- is **behavior-preserving** — consumers only change their import source, not their logic.

**Admin caveat (critical):** admin controllers are served under **two** prefixes (`/api/admin` +
`/api/application`), so Wayfinder emits **URI-keyed dictionaries**, not callables. Reference the
admin route explicitly, e.g. `LocationController.index['/api/admin/locations']`. Nested routes take
an args object keyed by the Laravel param name, e.g.
`updateRoute({ address_block_group: gid, address_block: bid })`.

Reference implementations: `features/overview/api.ts`, `features/servers/api.ts`, and the already-
migrated `features/{locations,nodes,users,template-groups}/api.ts`.

Verify each domain with: `npx tsc --noEmit --project ./` then `npx vite build` (restore the build
artifacts afterward with `git checkout -- public/build`). Commit per-domain.

## Done (committed on `next`)

| Domain | Commit | Feature module(s) |
|---|---|---|
| Vite vendor chunking + http.ts type-import | `8fbe8d27` | (vite.config.ts) |
| locations | `fb34b712` | `features/locations/api.ts` |
| handoff doc update | `36e3b714` | (docs) |
| nodes (core) | `9b464064` | `features/nodes/api.ts` |
| admin users | `24b29a4d` | `features/users/api.ts` |
| template-groups + templates | `3e3d42d8` | `features/template-groups/api.ts` (+ `templates/api.ts`) |
| IPAM (groups + blocks + addresses) | _pending commit_ | `features/ipam/{api,blocks/api,blocks/addresses/api}.ts` |

IPAM was a three-level nested domain under `api/admin/addressBlockGroups/`, split into three feature
modules under `features/ipam/` (AddressBlockGroupController → `api.ts`; AddressBlockController →
`blocks/api.ts`; AddressController → `blocks/addresses/api.ts`). All consumers repointed
(import-source only, default→named), old `api/admin/addressBlockGroups/` dir removed, tsc + build
clean.

> Note: `getCompatibleServers` lived under `addressBlocks/addresses/` in the old tree but its route
> is on the **group** (`/address-block-groups/{...}/compatible-servers`), so it's in
> `features/ipam/api.ts`, not the addresses module. `ServerPicker.tsx` imports it (+ its
> `swrKey` prop is a leftover queryKey name, not the shim).

## Remaining domains after IPAM (order)

1. ✅ **admin/servers** — DONE (`features/servers/admin/api.ts`). Kept distinct from the client
   `features/servers/api.ts`. Note: `updateState`/`getState` were NOT in the old `api/admin/servers/`
   dir (only `{createServer,getServer,getServers,use-server,use-servers}`) — state already lives in
   `features/servers/state/api.ts`. The admin `show` route param is typed `{server: uuid}` by
   Wayfinder but binds by id, so `getServer(id)` calls `showRoute(String(id))` to preserve the URL.
2. ✅ **node sub-resources** — DONE. `features/nodes/storages/api.ts` (StorageController) and
   `features/nodes/network-interfaces/api.ts` (NetworkInterfaceController). Controllers live under
   `wayfinder/.../Admin/Nodes/`. Storage `update`/`updateBackupOrder` are PUT; the whole old
   `api/admin/nodes/` dir is now gone. `LoadBalancerSidebar.tsx` (optimistic backup-order) repointed
   and still builds.
3. ✅ **account/authenticator** — DONE (`features/account/authenticator/api.ts`). The endpoints are
   spread across Fortify controllers (`AuthenticatorStatusController` + `Laravel/Fortify/.../
   {TwoFactorQrCodeController,TwoFactorSecretKeyController,RecoveryCodeController,
   TwoFactorAuthenticationController}`), all URI-keyed dicts sharing the stock `/user/*` routes — the
   `/api/client/account/authenticator/*` keys are POST for enable/disable/regenerate (note disable is
   the `destroy` export but POST for this URI).
4. ✅ **account/passkeys** — DONE (`features/account/passkeys/api.ts`). Client `PasskeyController` is
   clean callables (no dual-prefix dict): `index`/`create`(registration-options)/`store`
   (verify-registration)/`rename`/`destroy`. Transformer `rawDataToPasskey` preserved. Note
   `api/account/updatePassword.ts` still lives in the old tree (not part of this domain).
5. **client `api/servers/**`** — ⏳ NOT STARTED. THE MESSY ONE — needs a design decision before
   porting, not a rote import-swap. Files: `use-server`, `use-server-{state,deployment,resources,
   statistics}`, `use-addresses`, `use-template-groups`, `reinstallServer`, `retryInstallation`,
   `updateState`, and their `get*`.
   - **Name/namespace collision:** old `api/servers/use-server.ts` exports its own `serverQueries`
     keyed `['server', uuid]` over the legacy `Server`/`ServerStateData` types (hand-rolled inline
     transforms in `getServer.ts`/`getState.ts`), plus `preloadServer`. The already-migrated
     **`features/servers/api.ts` also exports `serverQueries`** but keyed `['servers']` over
     `App.Data.Server.ServerData` for the list/detail. These are two different query namespaces with
     the same name — decide whether to (a) keep the client per-server domain in a distinct module
     (e.g. `features/servers/detail/api.ts`) to avoid clobbering, or (b) unify onto `ServerData` and
     the new keys (bigger behavior change; touches many consumers). Recommend (a) to stay
     behavior-preserving.
   - `serverQueries.state` uses `refetchInterval: 50` (ms) and `resources` uses `60000`; preserve.
   - `templateGroups` does a **dynamic `import('@/lib/axios')`** and hits
     `/api/client/servers/{uuid}/settings/template-groups` → `rawDataToTemplateGroup`. Find the
     matching Client controller route object.
   - `features/servers/state/api.ts` already exists but points at the **admin** ServerController
     (`['admin','servers',uuid,'state']`); the old client `getState`/`updateState` hit
     `/api/client/servers/{uuid}/state` with key `['server',uuid,'state']` — these are distinct, do
     not conflate.
   - Map each endpoint to its Client controller under `wayfinder/.../Client/Servers/`. Client
     controllers are mostly clean callables (no URI-keyed dict), per the existing
     `features/servers/api.ts`.

`api/auth/use-user.ts` is the current-user/session hook (used by `_app.tsx`, `auth.tsx`, Avatar) —
migrate last / carefully; it has `cacheUser`/`currentUserQueries` helpers.

## Session progress (2026-07-06, second sitting)

Committed on `next` this session, each verified with tsc + `vite build` (build output is gitignored,
nothing to restore): IPAM (`9b459101`), admin servers (`3c7c0b40`), node storages + network
interfaces (`bc9a5f2b`), account authenticator (`4a9bf058`), account passkeys (`66f0e264`). Remaining:
client `api/servers/**` (item 5 above — needs the design decision) and `api/auth/use-user.ts`. Also
still in the old tree but out of the listed domains: `api/account/updatePassword.ts`.

## Open reconsideration (from user's side agent, 2026-07-06)

The user spun up an agent questioning whether this whole-project migration is worth the churn. Its
advice: the `features/*` pattern is only a **data-layer** convention today (just `api.ts`); a *full*
feature slice would co-locate components + types + transformers + routes per domain, which is a much
bigger bet. It recommended **convert-as-you-touch** over big-bang. The user nonetheless chose "plow
through everything" for the api-layer migration — but if scope is revisited, consider whether to
(a) stop and adopt convert-as-you-touch, or (b) extend slices past `api.ts`. Not decided.
