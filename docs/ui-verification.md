# UI verification harness

Frame-by-frame interaction checking for the panel, driven from the Docker sandbox.

A single still cannot show a sub-second glitch: a backdrop that flashes, a layout
that jumps one frame before settling, a drag whose content lags the cursor. The
harness records the frames the compositor actually produced and lays them out as
a contact sheet.

## Why CDP screencast rather than video

`Page.startScreencast` emits a frame **only when the page changes**, so the frame
count is itself a signal: a hover that produces thirty frames is repainting more
than it should. It also needs no ffmpeg, because the contact sheet is composed in
a page from the captured JPEGs and screenshotted.

## Layout

The scripts live in `.sbx/dev/uiverify/`, next to the `browser.mjs` the dev kit
already publishes, and are committed. Their output goes to `storage/uiverify/out/`,
which is gitignored (`/storage/uiverify` in `.gitignore`); `storage/` itself is
tracked, so the ignore is explicit rather than inherited.

| File | Purpose |
| --- | --- |
| `filmstrip.mjs` | `record()` captures frames around an action; `contactSheet()` renders them; `dumpFrames()` writes originals |
| `drag.mjs` | `dragSmooth()` for raw pointer handlers, `dragSortable()` for dnd-kit |
| `sweep.mjs` | Admin sweep: static render, client-side transitions, dialogs, command palette, theme, dark pass |
| `sweep-client.mjs` | Client-area sweep across the server detail tabs |
| `probe-drag.mjs` | Drives the avatar cropper as a worked example of a recorded drag |

Run them inside the sandbox, never from the macOS host. The macOS host has no PHP
and no usable ddev app; `sbx exec` reaches the sandbox straight from a normal shell:

```bash
sbx exec claude-panel -- bash -lc 'cd /Users/eric/Documents/GitHub/panel/.sbx/dev/uiverify && node sweep.mjs'
```

`sweep-client.mjs` takes `SWEEP_EMAIL` / `SWEEP_PASSWORD`, because the client area
scopes to servers you own even for a root admin, so the admin account sees none of
the seeded servers.

## Traps that produce false findings

These each cost a round of investigation. They are test bugs, not app bugs.

- **`page.goto()` is a full browser navigation** and paints a real white frame.
  Only a client-side link click shows whether the shell survives a route change.
  Drive transitions with `getByRole('link')`, not `goto`.
- **The collapsed sidebar has a hover flyout.** Playwright leaves the cursor where
  it clicked, which is inside the sidebar, so the rail reads as 256px wide and
  looks broken. Park the mouse away before measuring. Collapsed and unhovered, the
  `aside` is 56px and `paintedRight` is 56.
- **Not every "Add X" opens a dialog.** `Add node` is an `<a>` to
  `/admin/nodes/create` ("Enroll a node"). A button-role lookup reports a missing
  trigger that is on screen, and a dialog assertion on it reports a bug that does
  not exist.
- **Theme entries are `menuitemradio`, not `menuitem`.** A `menuitem` lookup finds
  them in the DOM and never matches, which reads as a dead control. The theme lives
  under the account menu (avatar → Theme → Light/Dark/System); only the auth screens
  have a standalone toggle.
- **dnd-kit has an activation constraint.** A single mouse move from A to B never
  activates the sensor, so the test passes while nothing moved. Use `dragSortable()`,
  which primes past the 8px threshold first.

## Baseline

Recorded against `main` at `f35a1691`, after rebuilding `public/build`.

- 14 admin routes: every one has an `h1`, **zero horizontal overflow at 1440 and
  390**, no console errors.
- Dialogs open with **exactly one backdrop** and close on Escape. The nested-dialog
  contract holds.
- Dark mode: all 14 routes, no near-white surfaces, no overflow.
- 10 client server tabs: all render with an `h1` and zero overflow at both widths.
- Avatar cropper drag: 11 frames over 286ms, smooth travel, no blank frames.

## Open findings

**Dialog title casing is inconsistent.** 24 titles are sentence case and 11 are
Title Case. Sentence case is the dominant convention and the one that matches the
house voice. The outliers:

`Attach Node`, `Authorization Required`, `Change Your Email`, `Change Your Password`,
`Create Backup`, `Crop Your Picture`, `Disable Authenticator`, `Enable Authenticator`,
`New Address Block`, `New Block Group`, `New Location`, `New Network Interface`.

`ISO Library` as an `h1` is the same issue outside a dialog. `Change Your Email` and
`Crop Your Picture` also carry a "your" the rest of the app does not use.

**A failed login cannot be told apart from a wrong password.** `login.lazy.tsx`
catches every rejection from `login()` and writes "Invalid email or password" onto
both fields. A 500 from `POST /api/auth/login` therefore renders as a credential
error. This was reached for real: a password written to the column without the
`hashed` cast makes `BcryptHasher->check()` throw, and the screen still says the
password is wrong. Surface non-422 failures separately, the way `NameserversCard`
already does for curated API messages.
