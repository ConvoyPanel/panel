# Node-create redesign — handoff

Status as of 2026-07-13. **DONE.** The redesign is implemented, passes `tsc` + `npm run build`,
and has been **verified end-to-end against the real PVE** (log in → fill → Test connection →
specs auto-fill + live meter → submit → node created in DB → deleted). Two blocker bugs were
found and fixed during the live pass (see "Live testing" below).

## What was built (done)

Redesigned the admin **Add-a-node** screen: moved it into the app layout, sectioned-row
form, capacity block "B1", and switched the connection toggles to real switches.

### Decisions locked (from the design iterations / artifacts)
- **Layout:** Design 1 "sectioned rows" (title+blurb in a left column, fields right,
  `divide-y` between sections). Not fullscreen anymore.
- **In the app shell**, not the Stripe-style `FullscreenLayout`. `servers.create` stays
  fullscreen — only nodes moved.
- **Page toolbar:** sticky title row with `Cancel` + `Add node` on the right (global
  header still owns search/avatar, so actions can't go there). Single sticky toolbar
  replaces the earlier top+bottom duplication idea.
- **Capacity = "B1":** two icon'd groups — **Processor** (`IconCpu`, live `= N vCPU`
  tag) and **Memory** (`IconDeviceSdCard`, **MiB/GiB toggle**, live usable-memory
  **meter + big number**).
- **Switches** replace the boxed checkboxes for root/priv-sep/TLS (defaults: root off,
  priv-sep off, TLS on).
- Card conventions written up in `docs/card-design.md` (references the shadcn create-page
  cards + source paths).

### Files
- **Moved route** out of `(fullscreen)/` → `resources/scripts/routes/_app/admin/_dashboard/nodes.create.{tsx,lazy.tsx}`
  (same URL `/admin/nodes/create`, now under the admin sidebar via `_dashboard.tsx`).
  Old `(fullscreen)/nodes.create.*` deleted. Route tree regenerates on build.
- New `resources/scripts/features/nodes/components/Create/SectionRow.tsx`.
- Rewrote `GeneralSettingsForm.tsx`, `ConnectionSettingsForm.tsx`,
  `SpecificationsSettingsForm.tsx` (this one is the capacity B1 impl, incl.
  `MemoryAmountField` unit toggle + `MemoryResult` live meter).
- New primitive `resources/scripts/components/ui/Switch/{Switch.tsx,index.ts}` (Base UI
  `@base-ui/react/switch`) + `resources/scripts/components/ui/Forms/SwitchForm.tsx`,
  exported from `Forms/index.ts`.
- `docs/card-design.md` (new).

Verified: `npx tsc --noEmit` = 0 errors; `npm run build` green (chunk `nodes.create.lazy`
builds, route registered as `/admin/nodes/create`).

### Known-but-deferred
- **MiB/GiB toggle:** MiB is always the stored value; GiB is a display conversion. Whole
  GiB is exact; typing *decimal* GiB rounds to nearest MiB. MiB entry is unaffected.
- Not yet clicked in a browser (see below).

## Live testing — DONE (verified end-to-end)

Drove the full flow with Playwright against the real PVE: log in as `test@test.com` →
`/admin/nodes/create` → fill connection → toggle the 3 switches (root ON, priv-sep ON, TLS
OFF) → **Test connection = 201 success** → specs auto-filled from PVE (sockets 1 / cores 6 /
cpus 6 / memory 15990 MiB) → live meter read **18.7 GiB usable / 15.6 GiB physical +20%** →
GiB toggle showed 15.62 → picked a location → **submit → node created (id 2), redirected to
`/admin/nodes/2`**. Confirmed the row in the web DB via the list API (`items[]`, memory
16766730240 = 15990 MiB, overallocate 20, verifyTls false, serversCount 0), then **deleted
it (DELETE `/api/admin/nodes/2` → 204)**. Only the seeded node remains.

### Two blocker bugs found + fixed during the live pass
1. **Route regression (the redesign's own bug).** Moving create to
   `_dashboard/nodes.create.tsx` made it a *child* of the `_dashboard/nodes` route, whose
   component was the nodes **list** (`nodes.lazy.tsx`) with no `<Outlet/>`. So
   `/admin/nodes/create` rendered the list, not the form (title said "New node" but the body
   was the table). **Fix:** split the list into an index route
   (`nodes.index.tsx` + `nodes.index.lazy.tsx`) and made `nodes.lazy.tsx` a plain layout
   (`component: Outlet`). Node detail (`admin/nodes.$nodeId`) is a separate route, unaffected.
2. **Test connection 500 (pre-existing backend bug).** `NodeConnectionTestController` did
   `ConnectionResultData::from($this->service->handle($node))`, but `handle()` already returns
   a `ConnectionResultData`. That redundant `::from()` re-ran Spatie's hydration pipeline,
   which recursed into the nested `NodeStatusData` and — because `fromRaw()` is a `from`-prefixed
   static method Spatie auto-detects as a magic factory — re-invoked `fromRaw()` on the
   *serialized DTO array* (keys `kernel`,`cpu`,… instead of raw PVE keys `current-kernel`,
   `cpuinfo`,…), throwing `Undefined array key`. **Fix:** controller now returns the Data
   object directly (`return $this->service->handle($node);`). This is why a CLI repro of
   `handle()` alone passed while the fpm/controller path 500'd.

### Minor a11y nit (not fixed — noted for later)
`MemoryAmountField` (`SpecificationsSettingsForm.tsx`) puts `FormControl` around
`<InputGroup>`, so shadcn's `FormControl` injects its `id`/aria onto the wrapper `<div>`, not
the inner `<input>`. Result: the "Amount" `<label>` isn't associated with the input and the
input has no `id`/`name` attribute (works fine via RHF; just not label-linked). Consider
wiring the id/name through `InputGroupInput`.

### Environment facts (still true, useful for future live passes)
- ddev up at `https://convoy.ddev.site`; frontend assets are a direct mount (no restart to
  pick up a `npm run build`).
- **PVE:** `us-southeast-2.performave.com:8006`, node `us-southeast-2`, PVE 9.2.2, 6 cores /
  ~16 GiB, self-signed (turn Verify TLS **off**). Creds in `.env` (`PROXMOX_*`).
- **ddev gotcha #1 — split databases:** `ddev psql` / `ddev exec php artisan` talk to a
  *different* Postgres than fpm/web. Verify web-DB state via the HTTP API (as done here), not
  `ddev psql`.
- **ddev gotcha #2 — OPcache `validate_timestamps=0`:** PHP edits need `ddev restart` to take
  effect (frontend is fine).
- Web admin user: `test@test.com` / `Zzz!98765` (HTTP login returns 200). Browser login lands
  on `/`; then `page.goto('/admin/nodes/create')` directly — don't wait for auto-redirect.
- Playwright driver + cleanup scripts live in this session's scratchpad
  (`drive-node-create.mjs`, `cleanup.mjs`); playwright is `--no-save` (not in package.json).

## Cleanup — DONE
- Test node `PW Test Node` (id 2) deleted from the web DB (204).
- No throwaway routes left (`__mkadmin` never existed this pass; verified `routes/` clean).
- Temporary `repro_conn.php` removed; no debug logging left in `NodeStatusData`.
