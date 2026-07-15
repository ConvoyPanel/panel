# Frontend Overhaul Audit

Status: audited 2026-07-13 against `next` after the base + nova rollout. The
shared Textarea, Select, Checkbox, DropdownMenu, Popover, Command/combobox,
DataTable toolbar, OTP, accessible icon actions, admin server Disks, DataTable
empty/filtered-empty states, shared Show all control, admin dashboard Nodes
card, and IPAM mobile-row work identified below has since been completed;
unchecked items remain.

⚠️ **Truncating inside `Item` needs two non-obvious overrides** (cost real time,
will recur on every row conversion). `ItemTitle` is a `w-fit` flex row, so
`truncate` on it never ellipsises — put the text in a `<span className='truncate'>`
inside a `w-full min-w-0` title. `ItemDescription` defaults to `line-clamp-2` +
`text-balance`; `text-wrap` is a longhand of `white-space`, so `text-balance`
silently overrides `truncate`'s `nowrap` (you get `text-overflow: ellipsis` with
`white-space: normal` — it wraps and the ellipsis never shows). Use
`block truncate text-nowrap`. `text-nowrap` is in tailwind-merge's `text-wrap`
group, so it genuinely replaces `text-balance`; plain `truncate` does not.

⚠️ **A title that is a `Link`/`Button` needs a third override** (found 2026-07-15
while converting the IPAM tables; the same bug was already latent in admin
Servers, admin Nodes, node Servers, and node IPAM). `buttonVariants` is
`inline-flex shrink-0`, so putting `truncate` on the link itself does nothing
twice over: `shrink-0` stops it shrinking inside `ItemTitle`'s flex row, and
`text-overflow` never applies to a flex container's own children. The link
escapes the row and `ItemContent`'s `overflow-x-hidden` clips it mid-word with
no ellipsis — the page does not overflow, so it looks fine at a glance and only
a long name reveals it. Use:

```tsx
<ItemTitle className='w-full min-w-0'>
    <Link className={cn(buttonVariants({ variant: 'link' }), 'h-auto min-w-0 max-w-full shrink p-0')}>
        <span className='truncate'>{name}</span>
    </Link>
</ItemTitle>
```

Verify with a genuinely long value — the `nodes` seed data carries
`A node with a deliberately long display name for truncation` for this.

This document tracks the remaining visual-system and screen-composition work in
the frontend overhaul. It is an implementation checklist, not a record of work
already completed. Historical detail belongs in Git history.

The target remains the shadcn create-page default: base variant, nova style. The
canonical values already used by `Input`, `Button`, and `Card` are:

- Controls: `h-8`, `rounded-lg`, no shadow, `ring-3` focus treatment.
- Cards: flat `ring-1 ring-foreground/10`, `rounded-xl`, standard 16px padding.
- Lists: shared `Item`/`ItemGroup`, using muted rows where appropriate.
- Layout: shared components, `gap-2`/`gap-4`, responsive desktop and mobile
  representations, and contextual empty states.

## Summary

The overhaul is partially complete. The theme, primary input/button/card
primitives, sidebar, account Security page, and several list/table screens use
the new system. However, some shared controls still use the previous styling,
which makes every consumer look partially migrated. Several older collection
screens also retain bespoke layouts.

The remaining work should be completed in this order:

1. Finish shared form and menu primitives.
2. Normalize DataTable toolbar controls and action buttons.
3. Recompose the admin server Disks page.
4. Convert remaining bespoke collection screens.
5. Complete responsive/mobile coverage.
6. Move remaining Radix primitives to Base UI screen-by-screen with browser
   interaction testing.

## Reported inconsistencies

### Textarea

The SSH key dialog correctly uses `TextareaForm`, but the shared primitive at
`resources/scripts/components/ui/Textarea.tsx` still has the old control chrome:

- `rounded-md` instead of `rounded-lg`.
- `shadow-xs` instead of no shadow.
- `px-3` instead of nova's `px-2.5` rhythm.
- `ring-1` instead of `ring-3 ring-ring/50`.
- Missing nova invalid, disabled, and dark-state treatments.

Because this is a shared primitive, the mismatch also appears in IPAM,
templates, node network forms, and the server SSH-key paste dialog.

### DataTable button sizes

The Locations toolbar exposes an inconsistent size contract:

- `DataTableViewOptions` requests `size="sm"` and then overrides it to `h-8`.
- `CreateLocationModal` uses the actual small button height, `h-7`.
- Both retain manual `mr-2` icon spacing even though `Button` now supplies its
  own gap.

This affects every DataTable toolbar with right-side actions, not only Locations.

### Admin server Disks page

`resources/scripts/features/servers/components/admin/detail/ServerDisksPanel.tsx`
predates the current collection patterns:

- A bespoke page heading/action row uses a nonstandard `gap-3`.
- The page title `Disks` is followed by a second collection title, `Attached
  disks`.
- An empty disk collection renders column headers and a blank body.
- The raw table has no mobile `Item` representation.
- Secondary-disk actions are full inline buttons rather than the standard
  compact action menu.
- The Add disk icon retains obsolete `mr-2` spacing.
- The route has no `staticData.title` metadata.

The page should use either the standard page-level collection/DataTable pattern
or a card-local `CardAction` pattern. It needs a contextual empty state with an
Add disk action and responsive rows.

### Select and dropdown controls

The API token screenshot is the shared Select, not a DropdownMenu. The Select
family remains on the previous visual system:

- `SelectTrigger`: fixed `h-9`, `rounded-md`, shadow, and `ring-1`.
- `SelectContent`: old rounded/bordered popup treatment.
- `SelectItem`: old item radius, padding, and focus treatment.
- No shared `sm`/`default` size API.

The actual `DropdownMenu` family was subsequently aligned with nova and given a
shared destructive item variant.

## Priority 0: shared primitives

### Textarea and form wrapper

- [x] Match `Textarea` chrome to the canonical `Input` values.
- [x] Preserve multiline-specific behavior and an appropriate minimum height.
- [x] Add matching invalid, disabled, and dark-state treatment.
- [x] Make `TextareaForm` combine caller-provided `disabled` with form submission
      state instead of overwriting it.
- [x] Browser-check the SSH key, IPAM, template, and network dialogs. Done
      2026-07-15 across all four (each now renders through the migrated
      `ResponsiveDialog`): dialog opens, multiline entry works, radius 10px
      (`rounded-lg`) and padding 10px (`px-2.5`), no console errors, and the
      390px drawer has no overflow. Resting `box-shadow` is `none`; it only
      appears on focus because Tailwind implements `ring-3` AS a box-shadow —
      measure unfocused or it reads as a false positive.

### Select

- [x] Align the default trigger with the nova control height, radius, padding,
      shadow, focus, invalid, disabled, and dark states.
- [x] Add a size API rather than relying on one-off height overrides.
- [x] Retain `h-auto` for intentionally rich, multi-line selectors.
- [x] Update popup, viewport, scroll buttons, and items as one component family.
- [x] Browser-check keyboard navigation, typeahead, focus return, collision
      handling, validation, and mobile dialogs. Done 2026-07-15 — and it found
      two real bugs in Select-inside-a-dialog (Escape closed the whole dialog;
      keyboard nav was dead because Radix's focus trap fought Base UI's portaled
      popup). Both are fixed at the root by the Dialog/Drawer/Sheet migration to
      Base UI; re-verified afterwards on the previously-broken `/admin/tokens`.
      Plain-page selects: Enter opens, ArrowDown highlights, Escape closes, focus
      returns to the trigger.
- [x] Migrate to Base UI during this work if its interaction contract can be
      verified on the owning screens.

### Checkbox, radio, and OTP

- [x] Move Checkbox off the old primary border, shadow, and one-pixel focus ring.
- [x] Add nova invalid, disabled, dark-state, and hit-target treatment.
- [x] Make `CheckboxForm` and `CheckboxItemForm` preserve caller `disabled` state.
- [x] Update the dormant RadioGroup primitive before introducing new consumers.
      Now Base UI, chrome mirroring the shared Checkbox (`border-input`, no
      shadow, `ring-3` focus, invalid/disabled, `after:` hit target). Verified via
      a throwaway route since it has no consumer to host it: `role=radiogroup`/
      `radio`, correct `aria-checked`, `data-checked`/`data-unchecked`, primary
      fill when checked, click and ArrowDown roving. `@radix-ui/react-radio-group`
      is uninstalled.
- [x] Align OTP slots with the current control dimensions and focus treatment.
- [x] Browser-check login authenticator entry and representative checkbox forms.

### Menus, command inputs, and comboboxes

- [x] Update DropdownMenu content and items to the current popup/item treatment.
- [x] Add a destructive menu-item variant and use it for delete/kill actions.
- [x] Normalize Command/combobox input heights; current consumers mix `h-9` and
      `h-10` while standard controls use `h-8`.
- [x] Give `ResourceComboboxForm` normal `FormControl` IDs, `aria-invalid`,
      description/error linkage, and combobox role/state semantics.
- [x] Replace duplicated bespoke Show all controls with the shared Button.

## Priority 1: buttons and DataTable

- [x] Define one toolbar action size contract and remove contradictory `size` plus
      height overrides.
- [x] Remove stale `mr-2`/`ml-2` icon margins now that Button supplies `gap`.
- [x] Remove the loading spinner's built-in manual margin from `Button`.
- [x] Use `size="icon"` consistently for pagination and action controls.
- [x] Add accessible names to icon-only buttons.
- [x] Make `DataTableToolbar` wrap or reflow on narrow screens.
- [x] Distinguish an empty collection from a filtered no-results state.
- [x] Allow contextual empty copy and a primary onboarding/create action.

Representative affected shared components:

- `resources/scripts/components/ui/DataTable/DataTableViewOptions.tsx`
- `resources/scripts/components/ui/DataTable/DataTableFacetedFilter.tsx`
- `resources/scripts/components/ui/DataTable/DataTableColumnHeader.tsx`
- `resources/scripts/components/ui/DataTable/DataTablePagination.tsx`
- `resources/scripts/components/ui/DataTable/DataTableToolbar.tsx`
- `resources/scripts/components/ui/Table/Actions.tsx`

## Priority 1: screen composition

### Admin server Disks

- [x] Adopt the standard page or card collection composition.
- [x] Add loading rows/skeletons that match the eventual layout.
- [x] Add a contextual empty state with Add disk as the primary action.
- [x] Replace inline row buttons with the standard action menu.
- [x] Add a mobile `Item` row representation.
- [x] Add route title metadata and normalize title casing.
- [ ] Verify add, resize, remove, primary-disk restrictions, and empty/loading
      states against a live seeded node.

### Remaining bespoke collections

These are the clearest screens that still predate the documented Item/DataTable
patterns:

- [x] Client My Servers: bespoke cards, no loading skeleton, and no empty state.
- [x] Admin Templates: bespoke cards and detached create/empty-state actions.
- [x] Node Network: bespoke cards and an empty CardHeader used as a spacing shim.
- [x] Node Storages: bespoke cards, detached actions, and spacing shims.
- [x] Server Boot Order: hand-built bordered list instead of Item/ItemGroup.
- [x] Server Addresses: raw desktop table and duplicated overflow handling.
- [x] Admin dashboard Nodes card: plain-text empty state, and hand-rolled divs for
      the mobile rows. The desktop/mobile split itself is **intentional and stays** —
      this line originally read as "convert the whole card to Item rows", which was
      tried and reverted: the dense table is a deliberate part of the dashboard
      redesign, and the meter is only legible because a column header (desktop) or
      an explicit label (mobile) names it. Only the mobile rows moved to shared
      `Item`/`ItemGroup`. Do not re-flag the split.

### Server subpage consistency

- [x] Replace residual `gap-5`/`gap-6` page spacing with the established responsive
      `gap-2`/`gap-4` rhythm where no semantic exception exists. Server subpages
      done; `Header`'s internal `gap-6` is component rhythm, not page spacing, and
      was left alone.
- [x] Give Graphs a page-specific heading instead of reusing the Overview header.
      Now `Resource usage`. Note this also drops the power Toolbar from Graphs
      (it came bundled in Overview's `Header`), matching every other subpage.
- [x] Consolidate Backups heading/quota/list controls into the normal page rhythm.
- [x] Remove hardcoded backup quota presentation and wire the empty-state action.
      The quota was fully mocked; it now reads real limits plus a new `backupSize`
      total. The empty-state action needed a create dialog built from scratch --
      there was no create-backup UI anywhere.
- [x] Review Rebuild's isolated width and spacing model against sibling pages.
      It was the last subpage with its own model: a `flex flex-col gap-y-6`
      wrapper (which made the page one flex child, defeating AppLayout's
      `gap-2`/`@md:gap-4`) plus a bespoke `max-w-xl`. Now on the shared
      one-column-of-two grid — measured identical to a Security card. It was also
      the only server subpage missing route `meta`.

## Priority 2: responsive coverage

`DataTable.mobileRow` is opt-in, so several tables still become horizontally
scrolling desktop tables on small screens.

- [x] Global admin Servers. The mobile row carries its own selection checkbox —
      the desktop selection column is not rendered below `@md`, so without it
      bulk power actions are unreachable on mobile.
- [x] Global admin IPAM groups.
- [x] IPAM address-block list.
- [x] IPAM attached-nodes list.
- [x] Address list within a block.
- [x] Admin server Disks.
- [x] Server Addresses.

Every converted page should be checked at desktop width and approximately 390px,
including long names, empty data, loading data, pagination, filters, and row
actions.

## Priority 2: Base UI follow-through

Progress, Separator, Tabs, Select, Checkbox, DropdownMenu, Popover, Toggle/
ToggleGroup, and Collapsible already use `@base-ui/react`. The following
families remain intentionally deferred because their composition and DOM
contracts differ from Radix:

- Dialogs and sheets.
- Menus, popovers, and tooltips.
- Selects and checkboxes.
- Scroll areas.
- `asChild` wrappers and triggers.

Migrate these opportunistically with their owning screen. Do not perform a blind
package-level replacement. Each migration requires interaction tests for focus,
keyboard behavior, dismissal, nested portals, mobile behavior, and accessible
names/descriptions.

⚠️ **Base UI's state attributes are not Radix's, and a wrong selector fails
silently** — Tailwind never errors on a class that matches nothing, so a ported
Radix selector compiles clean and simply never fires. Known mappings, each
verified against the rendered DOM (`@base-ui/react` 1.6.0):

| Component   | Radix                | Base UI                                        |
|-------------|----------------------|------------------------------------------------|
| Collapsible | `data-state=open`    | trigger `data-panel-open` (absent when closed); panel `data-open`/`data-closed` |
| ToggleGroup | `data-horizontal`    | `data-orientation="horizontal"`                |

The rule Base UI follows: its default state→attribute mapping emits a bare
`data-<key>` only when the state value is boolean `true`, and stringifies
otherwise (some components override this with a custom mapping — check
`utils/*StateMapping` in the dist before assuming). **Verify against the DOM,
not a green build.** The remaining Radix consumer is `Accordion`; Base UI ships an
equivalent when its owning screen comes up. (`RadioGroup` is migrated;
`Radio` uses `data-checked`/`data-unchecked`.)

The `Collapsible` primitive (`components/ui/Collapsible`) was added for the
backups Advanced disclosure. Prefer it over `Accordion` for a single disclosure —
it is Base UI, and it avoids adding a new Radix consumer.

## Intentional exceptions

Do not normalize these mechanically:

- Rich OS, template, network, and storage selectors may need `h-auto`.
- Local array editors may use shared controls without React Hook Form wrappers.
- Specialized full-row navigation and account setting controls need not look like
  ordinary buttons.
- Inline editors may intentionally use small submit buttons.
- Raw links/buttons using `buttonVariants` are acceptable when semantics require
  a native element and accessibility behavior is preserved.

## Separate product work

The bandwidth controls frontend is not part of this visual cleanup. It remains a
separate product feature in `v10-next-handoff.md`:

- Server-create speed cap.
- Existing-server limits and inheritance editor.
- Node overage override.
- Global BandwidthSettings administration.

The optional workspace/account switcher and destructive-button strength are also
product/design decisions, not migration defects.

## Definition of done

- [ ] Shared controls use one documented size, radius, focus, invalid, disabled,
      and dark-state system.
- [ ] No component requests one Button size and overrides it back to another.
- [ ] Icons rely on Button gap rather than call-site margins.
- [ ] Collection pages have loading, empty, populated, filtered-empty, and error
      behavior where applicable.
- [ ] Collection screens have deliberate mobile representations rather than
      accidental horizontal overflow.
- [ ] Destructive actions are visually and semantically distinct.
- [ ] Icon-only controls have accessible names.
- [ ] Base UI migrations retain keyboard, focus, portal, and dismissal behavior.
- [ ] `ddev npm run tc` and `ddev npm run build` pass.
- [ ] Flagship screens are browser-verified at desktop and mobile widths with no
      console errors or horizontal page overflow.
