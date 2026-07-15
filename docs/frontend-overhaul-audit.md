# Frontend Overhaul Audit

Status: audited 2026-07-13 against `next` after the base + nova rollout. The
shared Textarea, Select, Checkbox, DropdownMenu, Popover, Command/combobox,
DataTable toolbar, OTP, accessible icon actions, and admin server Disks work
identified below has since been completed; unchecked items remain.

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
- [ ] Browser-check the SSH key, IPAM, template, and network dialogs.

### Select

- [x] Align the default trigger with the nova control height, radius, padding,
      shadow, focus, invalid, disabled, and dark states.
- [x] Add a size API rather than relying on one-off height overrides.
- [x] Retain `h-auto` for intentionally rich, multi-line selectors.
- [x] Update popup, viewport, scroll buttons, and items as one component family.
- [ ] Browser-check keyboard navigation, typeahead, focus return, collision
      handling, validation, and mobile dialogs.
- [x] Migrate to Base UI during this work if its interaction contract can be
      verified on the owning screens.

### Checkbox, radio, and OTP

- [x] Move Checkbox off the old primary border, shadow, and one-pixel focus ring.
- [x] Add nova invalid, disabled, dark-state, and hit-target treatment.
- [x] Make `CheckboxForm` and `CheckboxItemForm` preserve caller `disabled` state.
- [ ] Update the dormant RadioGroup primitive before introducing new consumers.
- [x] Align OTP slots with the current control dimensions and focus treatment.
- [x] Browser-check login authenticator entry and representative checkbox forms.

### Menus, command inputs, and comboboxes

- [x] Update DropdownMenu content and items to the current popup/item treatment.
- [x] Add a destructive menu-item variant and use it for delete/kill actions.
- [x] Normalize Command/combobox input heights; current consumers mix `h-9` and
      `h-10` while standard controls use `h-8`.
- [x] Give `ResourceComboboxForm` normal `FormControl` IDs, `aria-invalid`,
      description/error linkage, and combobox role/state semantics.
- [ ] Replace duplicated bespoke Show all controls with the shared Button.

## Priority 1: buttons and DataTable

- [x] Define one toolbar action size contract and remove contradictory `size` plus
      height overrides.
- [x] Remove stale `mr-2`/`ml-2` icon margins now that Button supplies `gap`.
- [x] Remove the loading spinner's built-in manual margin from `Button`.
- [x] Use `size="icon"` consistently for pagination and action controls.
- [x] Add accessible names to icon-only buttons.
- [x] Make `DataTableToolbar` wrap or reflow on narrow screens.
- [ ] Distinguish an empty collection from a filtered no-results state.
- [ ] Allow contextual empty copy and a primary onboarding/create action.

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
- [ ] Admin Templates: bespoke cards and detached create/empty-state actions.
- [x] Node Network: bespoke cards and an empty CardHeader used as a spacing shim.
- [x] Node Storages: bespoke cards, detached actions, and spacing shims.
- [x] Server Boot Order: hand-built bordered list instead of Item/ItemGroup.
- [x] Server Addresses: raw desktop table and duplicated overflow handling.
- [ ] Admin dashboard Nodes card: bespoke desktop/mobile table split and plain-text
      empty state.

### Server subpage consistency

- [ ] Replace residual `gap-5`/`gap-6` page spacing with the established responsive
      `gap-2`/`gap-4` rhythm where no semantic exception exists.
- [ ] Give Graphs a page-specific heading instead of reusing the Overview header.
- [ ] Consolidate Backups heading/quota/list controls into the normal page rhythm.
- [ ] Remove hardcoded backup quota presentation and wire the empty-state action.
- [ ] Review Rebuild's isolated width and spacing model against sibling pages.

## Priority 2: responsive coverage

`DataTable.mobileRow` is opt-in, so several tables still become horizontally
scrolling desktop tables on small screens.

- [ ] Global admin Servers.
- [ ] Global admin IPAM groups.
- [ ] IPAM address-block list.
- [ ] IPAM attached-nodes list.
- [ ] Address list within a block.
- [x] Admin server Disks.
- [x] Server Addresses.

Every converted page should be checked at desktop width and approximately 390px,
including long names, empty data, loading data, pagination, filters, and row
actions.

## Priority 2: Base UI follow-through

Progress, Separator, and Tabs already use `@base-ui/react`. The following
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
