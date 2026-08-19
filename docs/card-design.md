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
| `Field` / `FieldGroup` / `FieldLabel` / `FieldDescription` | `components/ui/Field/` | `FieldGroup` stacks fields with `gap-5`; `Field` supports `orientation="horizontal"`; a `FieldLabel` wrapping a `Field` becomes a **radio card** (see below) |
| `InputGroup*` | `components/ui/InputGroup/` | input with inline addons/buttons |
| `InputForm` / `CheckboxForm` | `components/ui/Forms/` | RHF-wired field + label + error, our default inside forms |

### The radio card is already in `Field`

A **`FieldLabel` whose direct child is a `Field`** is a selectable card, and none of it
needs writing at the call site: the label picks up `rounded-lg border` and gives the field
`p-2.5`, then `has-data-checked:border-primary/30 has-data-checked:bg-primary/5`
(`/20` and `/10` in dark) tints it once the control inside reports `data-checked`.

```tsx
<FieldLabel htmlFor={id}>
  <Field orientation={'horizontal'}>
    <RadioGroupItem id={id} value={group.uuid} />
    <TemplateIconDisplay icon={group.icon} className={'size-5 shrink-0'} />
    <FieldContent>
      <FieldTitle>{group.name}</FieldTitle>
      <FieldDescription>{group.description}</FieldDescription>
    </FieldContent>
  </Field>
</FieldLabel>
```

Two things to keep in mind. The `Field` has to be a **direct** child or none of the
`has-[>[data-slot=field]]:` selectors match. And the state attribute is Base UI's
`data-checked`, not Radix's `data-state=checked` — a ported Radix selector compiles fine
and silently never matches.

Lay a set of them out with `grid-cols-[repeat(auto-fill,minmax(14rem,1fr))]` rather than a
`@md:` breakpoint when the same field renders at more than one width: the `@container` a
container query resolves against is `AppLayout`'s content area, not the card the cards are
sitting in (see the `@sm:`/`@md:` warning under *Do / don't*).

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

### Status colour is a token, including the good kind

`--destructive` always had a token and `--success` never did, so "this finished, and it
went well" got written as a literal `text-green-500` — one colour on the deployment screens
that no theme could reach and no dark-mode step ever touched. There is now a `--success`
token in both blocks of `app.css`, mapped in `tailwind.config.cjs` as `success`; use
`text-success` / `bg-success` (and `bg-success/40` for the rail between finished steps) the
way you already use `text-destructive`. Semantic colour is separate from the accent: blue
means "in progress", not "good".

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
  with `@xl:col-span-2`. See the reference layout cited under *In this codebase* below.
- **Do** keep body text at `text-sm` (the Card sets it) and descriptions
  `text-muted-foreground`.
- **Do** match the checkbox to the card it sits in. In a **settings** card a switch row
  (`justify-between`, `border-t` between rows) reads cleaner than a boxed one; in a card
  where the user is **choosing** things — a picker, a wizard step — use the boxed
  `CheckboxForm` (`rounded-lg border p-3`, checkbox + label + description), which is the
  same shape as the radio cards above it. A bare checkbox row among selection cards reads
  as a different design. The rebuild page's "Start the server when the install finishes"
  is the boxed case; `/security`'s toggles are the row case.
- **Don't** stack sections with giant `space-y-16` gaps (the old create-node page). Card
  padding + `FieldGroup` gaps already provide the rhythm.
- **Don't** read a `@sm:`/`@md:` in a card as a statement about *the card*. The
  `@container` is almost always `AppLayout`'s content wrapper (`AppLayout.tsx:42`), so
  these queries measure the **whole content area** — a card in a 4-col row is a quarter of
  what its own `@md:` is testing. Worse, a card's width need not be monotonic in the page
  width: if its `col-span` changes at a breakpoint it can get *narrower* as the page grows
  (see the overview's Specifications card). Pick these thresholds by measuring the rendered
  page, not by arithmetic, and put the measurement in the commit message.

## In this codebase

- **Reference layout:** `routes/_app/admin/servers.$serverId/settings.lazy.tsx` — Resources
  / Backups as a responsive card grid, with Bandwidth spanning both columns beneath.

  This citation used to point at `nodes.$nodeId/settings.lazy.tsx`, which is a worse
  example of the same pattern and is why the two were once read as contradicting each
  other. Put two cards side by side only when they hold **comparable amounts of field**;
  a grid row stretches every card to the tallest one, so pairing a two-field card with a
  six-field card buys nothing but dead space in the short one. Node settings had exactly
  that (General beside Connection) and now stacks full-width sections instead, letting
  each card's own internal grid supply the horizontal density. Neither shape is more
  correct than the other — the field counts decide.
- **A stack of cards that is a single task** — `routes/_app/servers.$serverUuid/rebuild.lazy.tsx`
  plus `features/servers/components/client/Rebuild/*`: operating system, version, password,
  in that order, with the destructive action in the last card's footer. Three things there
  are worth copying.

  **Cap the form, not the page.** The wrapper around the heading *and* the cards is
  `mx-auto w-full max-w-3xl`, the same move `nodes.$nodeId/settings.lazy.tsx:122` makes and
  for the same reason: `AppLayout` gives the page up to 1600px, and a form stretched that
  far pulls every label away from its control. Cap the page column instead and the
  breadcrumbs come with it; leave out the `mx-auto` and the whole form hugs the left edge
  under a full-width heading.

  **A card that fills its width beats a card with a measure inside it.** The first attempt
  capped the field group at 26rem inside a full-width card, which just moved the emptiness
  to the right of the inputs. Either the card is the width of its contents or the contents
  are the width of the card.

  **A form spanning several cards still submits once.** The `<form>` wraps the card stack,
  the submit lives in the last `CardFooter`, and it opens the confirmation rather than
  firing the mutation — so the fields are validated before anyone is asked to type a server
  name to confirm.
- **The two admin create screens are now the same screen twice.** Node
  (`routes/_app/admin/_dashboard/nodes.create.lazy.tsx` +
  `features/nodes/components/sections/*`) and server
  (`routes/_app/admin/_dashboard/servers.create.lazy.tsx` +
  `features/servers/components/admin/Create/sections/*`) both live in the app shell,
  cap the column at `max-w-4xl`, open with the shared sticky `FormToolbar`
  (`components/ui/FormToolbar`, carrying Cancel + submit), and stack `@container`
  cards under it. Copy one when adding a third; do not reintroduce `FullscreenLayout`,
  which no route uses any more.

  Inside a long card, split the fields into icon'd groups with the shared
  `GroupHeader` (`components/ui/Forms`) rather than letting a run of number boxes blur
  together — Processor and Memory on the node's capacity card.

### A form should ask only what is authored

The server create page was consistent with the node page and still hard to face: it put
24 controls on screen to collect the 8 answers that are actually written per server
(name, hostname, owner, node, storage, template group, template, password). The other 16
are taken as they arrive — from the form's defaults, or from the preset that just filled
them in — so asking for them at full weight is what made the page read as busy. Measured
before and after: **2,376px → 1,414px, 24 visible controls → 9, 7 cards → 5.**

Two devices did that, and both are worth reaching for on any long form:

- **`FieldFold` (`components/ui/Forms`) states its values instead of asking for them.**
  A collapsed row reads `2 vCPU · 2 GiB · 20 GiB · unmetered · no backups` with an Edit
  beside it. Nothing is hidden — the answers are on screen, they have just stopped being
  questions. Write the summary in the values themselves, never as a field count
  ("5 settings" tells the reader nothing they can check).

  It opens itself in the two cases where a fold would otherwise lie: when a field inside
  it is **dirty** (which is exactly what applying a preset does — `applyPresetSettings`
  passes `shouldDirty`, and RHF marks a field dirty only when the value actually differs
  from its default, so a preset's changes announce themselves and untouched groups stay
  shut), and when a field inside it **failed validation**, including the server errors
  `handleFormErrors` maps back after a rejected submit. A click always beats the dirty
  rule, and never beats the error rule — a fold that swallows the message leaves a
  submit that fails with nothing on screen to explain it. Everything in the group must
  be listed in `fields`, or its error is what gets swallowed.

- **A unit belongs to the value, not the question.** `InputForm`'s `suffix` puts `MiB` /
  `MB/s` / `GiB` inside the field's trailing edge, so `Memory` replaces
  `Memory (MiB)` *plus* a helper line. Note the wiring: `FormControl` goes around
  `InputGroupInput`, not around `InputGroup` — it clones its child to inject the `id` and
  aria, and on the wrapper those land on a `div`, leaving the label associated with
  nothing (the bug still open against the node page's `MemoryAmountField`).

The card count came down the same way: merge a card whose whole content is two pickers
(Placement) into the card it qualifies, and put a repeating list (extra disks) inside the
group it belongs to rather than giving it a header of its own to say it is empty.

## Statistic cards: a meter is not a footer

`StatisticCard` (`features/servers/components/client/Overview/`) is the compact
number-and-label tile used across the server overview. Three rules, each learned from the
way the overview row looked before 2026-07-17:

1. **A progress bar goes in the `meter` slot, not a `CardFooter`.** `CardFooter` is
   `border-t bg-muted/50`, so a bar placed there is ruled off and tinted like an action
   bar. Worse, the old card gave it `grow justify-end`, which in a stretched grid row pins
   the bar to the card's bottom edge — metres away from the number it measures. `meter`
   renders inside `CardContent`, directly under the value.
2. **Only render a meter when there is a real ratio.** A bar hard-coded to `0` because the
   data is missing does not read as "unknown", it reads as "empty" — the storage card drew
   an empty disk whenever the guest agent was down. No ratio, no bar.
3. **The value slot holds a number.** When the preferred figure is unavailable, fall back
   to one you *do* have and say so in the muted subline (storage shows the disk limit from
   the server record, sublined `Disk limit • guest agent offline`, plus a warning icon in
   the title). The old card put the string `Usage unavailable` where the number goes, which
   both broke the row's alignment and buried the limit it was already showing.

The corollary for the row as a whole: **every card in a statistic row uses `StatisticCard`.**
System Specifications was a plain `Card` with a `text-base` `CardHeader` sitting between two
tiles with `text-xs` compact headers, so the row read as two different designs colliding.
A card that opts out of the shared shell will not line up with its neighbours, no matter how
the grid is tuned.

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

## A clipped panel needs room for the focus ring

`CollapsiblePanel` and `AccordionContent` animate their height, which requires
`overflow: hidden` — and that clips at the panel's padding edge, cropping the 3px
`focus-visible:ring-3` of any control sitting against it. The first field in an open
"Advanced" disclosure loses the left of its ring; the last one loses the bottom.

This kept coming back because the bug lives in the primitive and only ever shows up at a
call site, so it gets patched locally (a stray `px-1` on somebody's form) and returns with
the next panel. **Both primitives now carry `clip-slack`** (`app.css`), which pads the clip
box by `0.25rem` and cancels the same amount in margin: the ring has somewhere to paint,
nothing moves, and a collapsed `h-0` panel still measures zero. Anything else that has to
clip a box containing focusable controls should use it too — don't re-solve this at the
call site.

Not `overflow-clip-margin`, which is designed for exactly this but isn't old enough to rely
on; and not "drop the clip once open", which races the height transition and lets content
spill mid-animation.

**Related trap, same shape:** `AlertDialogAction`/`AlertDialogCancel` used to paint
`buttonVariants()` on themselves. With `asChild` — which is how `ConfirmDialog` uses them —
Radix merges the *parent's* className last, so a `variant="destructive"` on the `Button`
inside was silently overridden and every destructive confirmation rendered primary-blue.
A wrapper that is not the button must not style like one.

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
