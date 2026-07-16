# Card design

How we build cards in the panel, and where the design comes from. Read this before
adding a new card-shaped surface (settings panels, create/edit forms, dashboard tiles)
so they stay consistent instead of each re-inventing padding, header layout, and footers.

## Where this comes from

Our card + form primitives are ported directly from **shadcn/ui's "create" (theme
customizer) page** — the gallery of demo cards shown at
<https://ui.shadcn.com/create> (Environment Variables, Invite Team, Book Appointment,
Report Bug, Weekly Fitness Summary, …). We took the **`base` + `nova` style** values
specifically; some of our primitives carry a comment saying so (e.g.
`components/ui/Card/Card.tsx`, `components/ui/Field/Field.tsx`).

### Upstream source paths

Repo: [`shadcn-ui/ui`](https://github.com/shadcn-ui/ui), branch `main`.

| What | Path |
| --- | --- |
| Customizer / "create" page (the app shell + control panel) | `apps/v4/app/(app)/(create)/` — `page.tsx` plus `components/*` (the pickers/customizer, **not** the demo cards) |
| **The demo cards in the gallery** | `apps/v4/registry/bases/base/blocks/preview/cards/*.tsx` |
| Preview assembler (lays the cards into the masonry grid) | `apps/v4/registry/bases/base/blocks/preview/index.tsx` |
| Card primitive | `apps/v4/registry/bases/base/ui/card.tsx` |
| Field primitive | `apps/v4/registry/bases/base/ui/field.tsx` |

Good demo cards to read when building a **form inside a card** (our most common case):

- `.../cards/report-bug.tsx` — title + description, `FieldGroup`, a two-column
  `grid grid-cols-2 gap-3` row, textarea, and a footer with a right-aligned button pair.
- `.../cards/book-appointment.tsx` — `CardContent` as `flex flex-col gap-4` mixing a
  `FieldGroup` with an `Alert`, footer with one full-width button.
- `.../cards/invite-team.tsx` — repeated rows, a `Separator` between sub-groups, and an
  `InputGroup` with an inline copy button.

> **Heads-up when reading upstream:** the `apps/v4/registry/bases/*` primitives express
> their styling through `cn-card`/`cn-*` CSS-layer utility classes, **not** inline
> Tailwind. We deliberately did **not** adopt that CSS-layer system — we kept the explicit
> Tailwind classes (the values match the `nova` base). So compare upstream *composition
> and structure*, and take our *class values* from our own primitives below.

## Our primitives

| Component | File | Base classes |
| --- | --- | --- |
| `Card` | `components/ui/Card/Card.tsx` | `flex flex-col rounded-xl bg-card text-sm text-card-foreground ring-1 ring-foreground/10` |
| `CardHeader` | `.../CardHeader.tsx` | `flex flex-col space-y-1 p-4` (switches to a `grid-cols-[1fr_auto]` layout when a `CardAction` child is present) |
| `CardTitle` | `.../CardTitle.tsx` | `text-base font-medium leading-snug` (renders `<h2>`; override with `as`) |
| `CardDescription` | `.../CardDescription.tsx` | `text-sm text-muted-foreground` |
| `CardAction` | `.../CardAction.tsx` | top-right slot in the header; needs `CardHeader`'s grid mode |
| `CardContent` | `.../CardContent.tsx` | `p-4 pt-0` |
| `CardFooter` | `.../CardFooter.tsx` | `flex items-center border-t bg-muted/50 p-4` |
| `Field` / `FieldGroup` / `FieldLabel` / `FieldDescription` | `components/ui/Field/` | `FieldGroup` stacks fields with `gap-5`; `Field` supports `orientation="horizontal"` |
| `InputGroup*` | `components/ui/InputGroup/` | input with inline addons/buttons |
| `InputForm` / `CheckboxForm` | `components/ui/Forms/` | RHF-wired field + label + error, our default inside forms |

The flat `ring-1 ring-foreground/10` (instead of `border` + `shadow`) is the defining look
of the `nova` base — cards read as quiet, inset surfaces rather than raised panels.

`flex flex-col` is upstream's too. We dropped it originally because we swapped its `gap-6`
for per-part padding and the column looked incidental — it is not. A card in a grid row is
stretched to the height of the tallest card beside it, and a plain block card leaves that
extra height at the bottom: `CardContent` keeps its own height, so anything centred inside
it centres against the header rather than the card. That is what made every empty state on
`/security` sit high with dead space under it. **A card whose content should fill the
stretched height gives `CardContent` `flex-1`** — see the four `/security` cards, which pair
it with `grid min-h-[12rem] place-items-center` so the empty state has a floor when the card
is *not* the tall one.

### Where we diverge from nova: field backgrounds

Upstream ships `Input` / `Textarea` / `InputGroup` / the `Select` trigger as
**`bg-transparent`**. That works upstream because every field there sits inside a white
`Card`, so transparent *renders* white. We put forms on tinted surfaces too — the node
create page lays sectioned rows straight onto `AppLayout`'s `bg-muted/40` — and there a
transparent field has nothing white beneath it and reads as washed-out or disabled rather
than fillable.

So **our field primitives use `bg-background`**. Two things make this safe rather than a
free-for-all:

- **Inside a `Card` it is a no-op** — the card is already `bg-card`, so white-on-white is
  identical to transparent. Nothing in the reference layout changes.
- **It mirrors what nova already does in dark mode.** The same primitives carry
  `dark:bg-input/30`: on a dark surface the base *already* gives fields their own fill
  instead of inheriting. We are applying that same intent to light mode.

Corollaries: don't "fix" a field by adding `bg-background` at the call site (it's in the
primitive), and don't reach for `Button variant="outline"` as a field-like trigger and then
override its background — `outline` is `bg-background` for button reasons, which is only
coincidentally the same value. `InputGroupInput`/`InputGroupTextarea` stay `bg-transparent`
on purpose: the `InputGroup` shell owns the fill, and a second one would double up.

## Anatomy — the rules

A card follows this order top-to-bottom. Skip parts, never reorder them.

```tsx
<Card>
  <CardHeader>
    <CardTitle>Connection</CardTitle>
    <CardDescription>Proxmox API endpoint & token.</CardDescription>
    {/* optional: <CardAction><Button .../></CardAction> */}
  </CardHeader>

  <CardContent className="flex flex-col gap-4">   {/* gap only if mixing groups */}
    <FieldGroup>
      <InputForm name="fqdn" label="FQDN" />
      <div className="grid grid-cols-2 gap-3">     {/* multi-column row */}
        <InputForm name="tokenId" label="Token ID" />
        <InputForm name="tokenSecret" label="Token secret" type="password" />
      </div>
    </FieldGroup>
    {/* an <Alert> or <Separator> + another group can follow here */}
  </CardContent>

  <CardFooter>
    <Button className="w-full">Add node</Button>       {/* or a right-aligned pair */}
  </CardFooter>
</Card>
```

1. **Header is always title + one-line description.** Never a bare title. The description
   says what the card is *for*, in the user's words, not the system's.
2. **Content groups fields with `FieldGroup`**, which owns vertical rhythm (`gap-5`) — do
   not hand-space fields with `space-y-*`. Multi-column rows are `grid grid-cols-2 gap-3`
   *inside* the group.
3. **Mixing a field group with a non-field block** (an `Alert`, a preview, a second group)
   → give `CardContent` `className="flex flex-col gap-4"` and let each block sit as a
   flex child. Separate two field sub-groups with `<Separator />`.
4. **The primary action lives in `CardFooter`**, which is visually divided (`border-t
   bg-muted/50`). Either a single **full-width** button (`className="w-full"`) or a
   **right-aligned pair** (wrap in a `Field orientation="horizontal"` with `justify-end`,
   secondary action as `variant="outline"` first, primary last).
5. **Header-level actions** (a "New", a menu) go in `CardAction`, not the footer.
6. **Radius/ring/padding come from the primitives.** Don't re-declare `rounded-*`,
   `border`, `shadow`, or `p-*` on a `Card`/`CardContent` — override only for a deliberate
   exception, and note why.

## Do / don't

- **Do** let a page be a grid of cards (`grid gap-4 @xl:grid-cols-2`), sizing a wide card
  with `@xl:col-span-2`. See `routes/_app/admin/nodes.$nodeId/settings.lazy.tsx` for the
  reference layout.
- **Do** keep body text at `text-sm` (the Card sets it) and descriptions
  `text-muted-foreground`.
- **Don't** wrap checkboxes in their own bordered boxes inside a card — inside a settings
  card, a switch row (`justify-between`, `border-t` between rows) reads cleaner than the
  boxed `CheckboxForm`.
- **Don't** stack sections with giant `space-y-16` gaps (the old create-node page). Card
  padding + `FieldGroup` gaps already provide the rhythm.

## In this codebase

- **Reference layout:** `routes/_app/admin/nodes.$nodeId/settings.lazy.tsx` — General /
  Connection / Specifications as a responsive card grid.
- The node **create** screen (`routes/_app/admin/(fullscreen)/nodes.create.lazy.tsx` +
  `features/nodes/components/Create/*`) predates this pattern and is the next thing to
  bring in line — see the redesign directions discussed in the v10 handoff.

## Boolean form fields must never be handed `undefined`

`CheckboxForm`, `CheckboxItemForm` and `SwitchForm` all pass `field.value ?? false` to the
Base UI primitive. The `?? false` is load-bearing, not defensive noise.

Base UI decides once, on the **first render**, whether a component is controlled, and
remembers that for the component's whole life (`useControlled.mjs`:
`const { current: isControlled } = React.useRef(controlled !== undefined)`). A form built
as `useForm({ resolver })` with **no `defaultValues`** yields `field.value === undefined`
on that first render, so the primitive latches *uncontrolled* and then ignores every value
it is given afterwards — including everything a later `form.reset(...)` supplies.

This is worse than a stale display. The box renders from Base UI's own internal state, so
it starts unchecked no matter what the record says, and the first click toggles that
internal state `false → true` and reports `onCheckedChange(true)` — meaning a user who
clicks once to "turn the thing off" submits it **on**. It shipped in the node settings page,
where `verify_tls` was stuck on and could not be turned off through the UI at all; every
Proxmox call failed TLS verification and the node's live cards never loaded.

It is silent in production: Base UI's controlled/uncontrolled warning is behind
`NODE_ENV !== 'production'`, and text inputs in the same form populate normally from
`reset()`, so the form looks like it works.

Prefer giving `useForm` real `defaultValues` as well — but the `?? false` in the primitives
is what makes every consumer safe by default.

## Dialog family, Tabs, and Select

These primitives use the same `base` + `nova` source as Card, but a few deliberate
local choices are easy to mistake for drift:

- `DialogContent` keeps `sm:max-w-lg` rather than nova's `sm:max-w-sm`; our dialogs
  routinely hold lists and forms. Its `p-4`, rounded popover, and flat ring still come
  from nova, so consumers should not add their own shell padding, radius, or shadow.
- `DialogContent`'s grid is `grid-cols-[minmax(0,1fr)]`, not the implicit `auto` column.
  A grid item's automatic minimum size is its min-content, so a track can never be
  narrower than the longest unbreakable string inside it: one pasted SSH key sized the
  column past `sm:max-w-lg` while the popup's own background stayed capped, and the
  header, fields and footer rendered *outside* the popup they belong to. The
  `minmax(0, …)` floor lets the column shrink so the content wraps instead. **Any long
  opaque value — a key, token, or ID — needs this**; `max-w-*` alone does not contain it.
- Dialog enter/exit uses Base UI's `data-starting-style` / `data-ending-style`
  transitions, not nova's `animate-in` transform keyframes. Nested dialogs also scale
  and move their parent; a transform keyframe would compete for those same properties.
- Nested desktop dialogs stay centred. `DialogContent` measures each popup's unscaled
  border-box height and publishes it through the dialog stack so the visible parent
  ledge remains exactly `1rem` regardless of either dialog's content height.
- `ResponsiveDialog` resolves the `md` breakpoint synchronously and once at its root.
  Mantine's default effect-time initial value briefly mounts a desktop Dialog as a
  Drawer, then remounts the entire subtree. Do not change
  `getInitialValueInEffect: false` or call the media query independently in each part.
- `TabsList` leaves Base UI's `activateOnFocus` at `false`: arrow keys move focus and
  Enter/Space activates. Automatic activation can trigger side effects merely while a
  keyboard user moves past a tab (the passkey tab starts a WebAuthn ceremony).
- `SelectContent` follows nova's native-select-like `alignItemWithTrigger=true`. Base UI
  reports `data-side="none"` in that mode, and we intentionally disable its open/close
  animation. Pass `false` only when the popup must behave as a menu below the trigger.
- Select row padding lives on `Select.List`, not only on nova's `SelectGroup`, because
  every current consumer places `SelectItem` directly in the list. `SelectGroup` keeps
  its own padding for future grouped content; do not nest both padded paths unchanged.

For mobile composition, use `ResponsiveDialogBody` and `ResponsiveDialogFooter` rather
than branching classes at the call site. The Drawer owns no popup padding while the
desktop Dialog does; those shared parts already reconcile the difference.
