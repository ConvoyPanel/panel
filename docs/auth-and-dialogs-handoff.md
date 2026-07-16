# Handoff — account security + the dialog family

Written 2026-07-16. Covers two threads: the Dialog/nova work (landed) and an
auth overhaul (designed in the original session and completed in the follow-up).

## Completion update

The follow-up completed every item this handoff called out:

- Nested dialogs now measure their unscaled border boxes and keep a constant
  `1rem` parent ledge. Browser measurements for the four cases below were all
  15.99px at 1280×900.
- Passkey registration and authentication require WebAuthn user verification.
- Password login challenges accounts with either a confirmed authenticator or
  a passkey; the guest passkey challenge is bound to Fortify's pending
  `login.id`. Direct passkey login remains a complete login method.
- Recovery codes are account-level: first-passkey registration issues and shows
  them, existing passkey users are backfilled, and adding/removing an
  authenticator preserves them while another factor remains.
- The challenge screen offers authenticator, passkey, and recovery methods based
  on the pending account.
- `Select alignItemWithTrigger` was browser-verified on the global bandwidth
  form and inside the API-token dialog (`data-side="none"`, no animation, popup
  within the viewport). The dialog-family conventions are now recorded in
  `docs/card-design.md`.

Verification: full PHP suite (315 tests / 845 assertions), frontend
typecheck and production build, plus a complete virtual-authenticator browser
flow (register passkey → receive eight recovery codes → password login → passkey
second-factor challenge → authenticated).

Commits this session, oldest first:

| SHA | What |
| --- | --- |
| `ca3f9a88` | dialogs stop mounting as drawers; nova migration; nested offset; Tabs/Select |
| `9a8d2066` | API/SSH key writes require a confirmed identity |
| `f86ab77b` | passkey registration origin fix; error surfacing; nested drawers |
| `567fcbe8` | TOTP confirmation step + `two_factor_confirmed_at` migration |
| `aaaeded4` | setup no longer hands out a QR for a discarded secret |

---

## 1. The nested-dialog peek — RESOLVED

### The problem, stated exactly

Two dialogs are each centred independently. With the parent offset by a constant
and scaled by `s`, the visible ledge below the child is:

```
peek = offset + (s · parentHeight − frontmostHeight) / 2
```

**The height term never cancels.** So the peek drifts with either dialog's
content and inverts once they are tall. Measured on `/security` at 1280×900:
Passkeys (empty list) 10px, Authenticator 25–28px. This is what "the offset is
weird sometimes" is.

### There is no upstream answer to copy

- **nova ships no nesting cue at all.** Its `dialog.tsx` popup class string has
  no `--nested-dialogs`, no offset, no tint.
- **Base UI ships the hook, not the answer**: *"Use the `[data-nested-dialog-open]`
  selector and the `var(--nested-dialogs)` CSS variable to customize the styling
  of the parent dialog."* A count, and nothing else.
- The tell is the asymmetry in their own vars:

  ```
  DrawerPopupCssVars: --nested-drawers, --drawer-height, --drawer-frontmost-height, …
  DialogPopupCssVars: --nested-dialogs        ← the entire list
  ```

  Drawers get a frontmost height because a stacked sheet needs it. Dialogs do not.

- **Base UI's docs demo has the same flaw.** Transcribing its exact CSS
  (`scale 0.9`, `top: calc(50% + 1.25rem*var(--nested-dialogs))`) and sweeping
  equal heights:

  ```
  160px → 11.9px    320px →  3.9px
  200px →  9.9px    400px → -0.1px  (parent hidden)
  260px →  6.9px    560px → -8.1px  (parent hidden)
  ```

  It only looks deliberate because both demo dialogs are a fixed `w-96` at ~200px.
  Our current `DialogContent` **is** that demo, faithfully. The wobble is
  inherited, not introduced.

### Decision

**Measure the frontmost popup in JS and keep every dialog centred** — i.e. build
for dialogs what Base UI ships for drawers. (Rejected: top-anchoring the stack;
dropping the cue; accepting it.)

### Design (verified maths, unverified code)

Publish two vars per popup and let CSS cancel the height term:

```
--peek: 1rem
--stack-step: 0.05
--has-nested:  min(var(--nested-dialogs,0), 1)
--stack-scale: calc(1 - var(--stack-step) * var(--nested-dialogs,0))
--stack-shift: calc(var(--peek)*var(--nested-dialogs,0)
               + var(--has-nested)
                 * (var(--frontmost-dialog-height,0px)
                    - var(--stack-scale)*var(--dialog-height,0px)) / 2)
top:   calc(50% + var(--stack-shift))
scale: var(--stack-scale)
```

Check the algebra:

- frontmost dialog (`n=0`): shift `= 0 + 0·(…)` → `top: 50%` → centred. ✓
- parent (`n=1`): shift `= peek + (hc − 0.95·hp)/2`
  → visual bottom `= T + shift + 0.95·hp/2 = T + peek + hc/2` = child bottom + peek. ✓
  **Independent of both heights.** That is the whole point.

`--has-nested` is load-bearing, and not for `n=0` cosmetics: `--nested-dialogs`
drops to 0 the instant a child starts closing, while the child stays mounted and
reporting through its exit transition. Without the multiplier the parent lurches
by `(hc − hp)/2` mid-exit.

`--dialog-height` / `--frontmost-dialog-height` must come from JS:

- Each `DialogContent` measures its own **layout** height (ResizeObserver
  `borderBoxSize`, not `getBoundingClientRect` — `scale` does not change the
  border box, so this stays stable while scaled back).
- A `DialogStackContext` is provided by each `DialogContent` around `{children}`;
  a nested dialog reports the frontmost height of *its* subtree up to it, and
  `null` on unmount. `frontmost = firstReportedDescendant ?? ownHeight`.
- Merging `style` is safe: Base UI's `mergeProps` merges the `style` object
  (`mergeObjects`), so `--nested-dialogs` survives alongside our vars. Verified
  in `merge-props/mergeProps.mjs:129`.

### Where my attempt broke (start here)

The WIP was reverted rather than left broken. Symptoms when it ran: parent's
`--frontmost-dialog-height` computed to `0px` and the peek went to **−112px**
(worse than before). Two suspects, unproven:

1. **The ref never fired.** `ownHeight` stayed `0`, so the ResizeObserver never
   attached — `ref={setPopup}` on `DialogPrimitive.Popup` may not reach the DOM
   node the way I assumed. Check with a plain `ref={el => console.log(el)}` first.
   Note the `style` vars *did* reach the DOM, so prop merging was fine.
2. **`min()` inside a Tailwind arbitrary property.** `[--has-nested:min(var(--nested-dialogs,0),1)]`
   contains commas; if Tailwind mangles it, `--stack-shift` becomes invalid, `top`
   falls back to `auto`, and the popup positions from the top of the viewport —
   which would explain −112px far better than a merely-zero height. **Check this
   one first**: inspect the computed `--has-nested`/`--stack-shift` in devtools.
   If it is the problem, move these vars into a real CSS file or `@utility`
   instead of arbitrary properties.

Verify with this, which is how the bug was found (expect a constant peek across
all four rows):

```
Passkeys      + Password tab   parentH=253 gateH=256  peek=?
Passkeys      + Passkey  tab   parentH=253 gateH=261  peek=?
Authenticator + Password tab   parentH=287 gateH=256  peek=?
Authenticator + Passkey  tab   parentH=287 gateH=261  peek=?
```

---

## 2. Auth overhaul — IMPLEMENTED

### The problem

**A passkey buys zero login security today.** An account is only as strong as its
weakest enabled path, and password login is still a fully-open single factor. An
attacker with the password ignores the passkey entirely. Registering one is a
convenience feature, not a lock — it adds a second door rather than a stronger one.

Current state of the three surfaces:

| Surface | Methods |
| --- | --- |
| Login | password → TOTP challenge · **passkey → straight in, no TOTP** · OAuth · SSO |
| Identity confirmation (sudo) | password · passkey |
| "2FA" | TOTP only |

`PasskeyLoginController::store()` calls `auth()->login($user)` and never consults
two-factor state, so a passkey login skips the TOTP challenge. That is defensible
— a passkey with user verification *is* two factors — but nothing enforces the
precondition (see below). Checked and **not** exploitable: a password-only
attacker cannot register their own passkey to bypass TOTP, because reaching
registration needs a session, which needs TOTP.

### Decisions taken

1. **2FA = TOTP *or* passkey.** Password login always requires a second factor,
   satisfied by either. Passkey-only login keeps skipping the challenge. This
   closes the password-alone path without removing password login.
2. **Enforce `userVerification: 'required'`**, folded into this work.
3. **Issue recovery codes to passkey-only users** — decouple them from TOTP
   enablement, so any second factor has a break-glass path.
4. Passkey-only mode (disabling password login) is **deferred**, not rejected.

### Why UV enforcement matters

`GeneratePasskeyAuthenticationOptionsAction` builds
`PublicKeyCredentialRequestOptions(challenge, rpId, allowCredentials: [])` with
**no `userVerification`**, so webauthn-lib defaults to `preferred`: the
authenticator *should* verify, but a `UV=false` assertion is accepted anyway. Our
`ConfigureCeremonyStepManagerFactoryAction` only touches origins.

In practice every platform authenticator (Touch ID, 1Password, iCloud Keychain)
always does UV, which is why this is invisible. The threat is an attacker who is
*not* using one — a scripted authenticator, or a key with UV off — replaying a
stolen credential. Today the "a passkey is two factors" claim is **incidental
rather than enforced**, and the TOTP bypass rests on it. `config/passkeys.php`
already points at swappable action classes, so this is a small subclass.

### Implementation sketch

1. **UV**: subclass the two option-generating actions to set
   `userVerification: 'required'` for registration *and* authentication. (Breaks
   ancient U2F keys with no PIN; irrelevant for passkeys.)
2. **A method-agnostic second factor**: "has 2FA" becomes *TOTP or a UV passkey*.
   `AuthenticatorStatusController` already defers to
   `hasEnabledTwoFactorAuthentication()`; the notion needs widening beyond it.
3. **The hard part — Fortify hard-codes `two_factor_secret`** as the challenge
   trigger (`RedirectIfTwoFactorAuthenticatable`). Needs a custom
   `Fortify::authenticateThrough()` pipeline. This is the real work, not the UI.
4. **The other sharp edge — the challenge step has no logged-in user.** Fortify
   keeps a pending `login.id` in the session, so a passkey-challenge endpoint must
   live in the `guest` group and **bind the assertion to that pending user**
   rather than logging in whoever the credential resolves to.
5. **Recovery codes**: decouple minting from TOTP enable.
6. UI: offer "use your passkey" beside the 6-digit code on the challenge screen.

---

## 3. Landed this session — the non-obvious bits

- **Dialogs mounted as Drawers.** `useMediaQuery(DESKTOP_QUERY, false)` — mantine
  defaults to `getInitialValueInEffect: true`, so it returned the `false`
  *initialValue* on first render. Every desktop dialog mounted as a Drawer, then
  swapped to a Dialog. Different component types ⇒ full remount ⇒ replayed enter
  transitions *and* a re-created Base UI store, which let a nested gate miss its
  parent's `DialogRootContext` and paint its own backdrop over the parent. One
  line; three symptoms.
- **A popup that mounts already-open never animates in.** `useTransitionStatus`
  seeds `mounted` from `open`, so `data-starting-style` never applies. `AuthDialog`
  mounts inside the dialog it guards, by which point identity is already
  unconfirmed. Fix: mount closed, open a tick later.
- **Passkey registration was dead on every local canary build.**
  `setAllowedOrigins(['localhost'])` looks like a companion to
  `setSecuredRelyingPartyId(['localhost'])` but is not: `CheckAllowedOrigins`
  treats a non-empty list as an *exhaustive allowlist* and stops matching the
  rp-id, so `https://convoy.ddev.site` was rejected. Only
  `setSecuredRelyingPartyId` is needed. Regression test in
  `tests/Unit/Actions/Auth/ConfigureCeremonyStepManagerFactoryActionTest.php`
  (confirmed to fail against the old code).
- **TOTP setup handed out a QR for a discarded secret.** The QR/secret were read
  through the query cache, but they describe one setup attempt — `enable` mints a
  new secret whenever there is none. The queries are disabled while minting, and
  invalidating an inactive query only marks it stale, so re-entering setup painted
  the *previous* attempt's QR from cache. Scanning that window seeded the
  authenticator with a dead secret → every code rejected, forever. `removeQueries`
  is **not** enough (the mounted hook keeps serving its observer's last result);
  they are now fetched into component state and the cached queries are gone.
- **`confirm => true` needed a migration, not just a config flip.** Fortify's
  two-factor migration only creates `two_factor_confirmed_at`
  `if (Fortify::confirmsTwoFactorAuthentication())` — false when every existing
  install ran it. Writes died with `SQLSTATE[42703]`. The new migration is guarded
  *and* backfills anyone holding a secret: they enabled under the old rule, and a
  null timestamp would silently stop challenging them at login.
- **`TabsList` forced `activateOnFocus={true}`** (Base UI defaults to `false`), so
  arrow-keying onto AuthDialog's Passkey tab fired a WebAuthn ceremony.

---

## 3b. Follow-up — recovery codes got one home (and a review pass)

The auth overhaul made recovery codes account-level in the *data* but left the
*UI* presenting them per-factor, so one set read as two:

- Both buttons hit the same `users.two_factor_recovery_codes`;
  `PasskeysMainDialog` even imported `getRecoveryCodes` from
  `authenticator/api.ts`. The two dialogs then described the same eight codes as
  "if you lose your authenticator app" and "if you lose your passkey", and
  "Reset recovery codes" in one silently invalidated what the other had just told
  the user to save.
- Fixed by giving them their own `AuthSetting` row beside Authenticator and
  Passkeys (`RecoveryCodesMainDialog`), which renders only once the account has
  codes. The routes moved off the TOTP-shaped `/account/authenticator/recovery-codes`
  to `/account/recovery-codes`, plus an ungated `/status` — codes exist exactly
  when a second factor does, so their presence *is* the account-level signal and
  the security page can read it before identity is confirmed.
- Each enable flow keeps only the one-time reveal of what it minted, through a
  shared `RecoveryCodesRevealDialog` with factor-agnostic copy. Enabling an
  authenticator on an account that already has a passkey mints nothing, so it
  reveals nothing — `AuthenticatorEnableDialog` reads the status *before*
  `enable`, the only moment that is knowable.

Two bugs found reviewing the overhaul, one fixed:

- **Fixed — the TOTP challenge 500'd for passkey-only accounts.** Since password
  logins are challenged on either factor, such an account reaches
  `/auth/authenticator/verify-challenge` with `two_factor_secret` null, and
  Fortify's `TwoFactorLoginRequest::hasValidCode()` decrypts that column guarded
  only by `$this->code &&` — `decrypt(null)` throws. The challenge screen hides
  the field (`authenticator: false`), but the endpoint does not care what the UI
  offers. `SecondFactorLoginRequest` guards it; bound in `FortifyServiceProvider`
  because the controller type-hints the parent.
- **Fixed — two flows never consumed their challenge, and shared a key.** Both
  `PasskeyLoginController::store` and `ConfirmableIdentityController::store`
  `get` their options where `SecondFactorChallengeController` correctly `pull`s,
  so the challenge stayed valid indefinitely and a captured assertion remained
  replayable against the same session. That is worst for identity confirmation,
  whose entire job is proving presence *now*: a replay could re-confirm after the
  5-minute window lapsed. Both `pull` now, and both guard `is_string` — the
  options are typed non-nullable on `FindPasskeyToAuthenticateAction::execute()`,
  so a verify with no create before it was a TypeError and a 500 rather than a
  rejected attempt. They also both wrote `passkeys.authentication-options`;
  identity confirmation now owns `passkeys.identity-options`, so a challenge
  minted to log in can never satisfy one minted to prove presence.
  `tests/Feature/Auth/PasskeyChallengeTest.php` covers all three, and every case
  was confirmed to fail against the old code first.
- Fixed — `GeneratePasskeyAuthenticationOptionsAction` no longer inherits Spatie's
  `Session::put('passkey-authentication-options', …)`. Nothing read it: its only
  reader is the package's own `AuthenticateUsingPasskeyController`, reachable only
  through the `Route::passkeys()` macro, which this app never calls. Each of the
  three callers stashes under its own key instead.

**The skeleton delay** on the passkey list was React Query retrying the identity
403. The list fetched on page mount, 403'd behind `RequireIdentityConfirmation`,
and the default `retry: 3` with exponential backoff (1s/2s/4s) kept it pending;
whichever retry landed after the user confirmed is what painted. Queries behind
that middleware now gate on `useIdentityConfirmed()` — confirming re-renders the
subscriber, which is the fetch trigger. Measured 311ms from confirm to list,
against multiple seconds before. Note `features/auth/api.ts` had already set
`retry: false` on `secondFactorQueries.methods()` for the same reason; the
general lesson is that a query behind an identity gate must not fire before it.

## 4. Outstanding

- **`Select alignItemWithTrigger` was flipped to nova's `true` but never verified
  in a browser.** It changes positioning for all 6 selects; `PenaltyActionFields`
  (admin bandwidth rules) was never reached. Selects inside scrolling dialogs are
  where item-alignment misbehaves. Note `data-side` becomes `"none"` in that mode,
  so the `data-[side=…]` slide classes never match and
  `data-[align-trigger=true]:animate-none` cancels the rest — nova's intended
  behaviour, but it means selects open with no animation.
- **`docs/card-design.md` covers Card/Field/Input/InputGroup and says nothing
  about Dialog/Drawer/ResponsiveDialog/Tabs/Select.** Nearly every UI bug this
  session was an *undocumented* divergence from an upstream default —
  `activateOnFocus`, `alignItemWithTrigger`, Select's padding living on
  `SelectGroup`, and a comment citing `initializeWithValue` (a **usehooks-ts**
  option mantine does not have). Divergence is often right; unrecorded divergence
  is indistinguishable from an accident. Extend that doc to the dialog family.
- Deliberate divergences to record: Base UI transitions over nova's `animate-in`
  keyframes (a keyframed transform fights the nesting scale/offset for the same
  properties); `sm:max-w-lg` over nova's `sm:max-w-sm`; Select padding on `List`
  rather than `SelectGroup` (no consumer uses `SelectGroup`).

## 5. Environment

- `visual-admin@example.test` has password `Password123!`, set for browser
  verification. Its 2FA/passkeys were reset repeatedly via SQL:
  ```sh
  ddev exec psql -U db -d db -c "UPDATE users SET two_factor_secret=NULL, two_factor_recovery_codes=NULL, two_factor_confirmed_at=NULL WHERE email='visual-admin@example.test';"
  ```
- **`npm run build` deletes `public/hot`**, so HMR stays dead until vite is
  restarted. A stale build silently serving old assets cost real debugging time —
  if a change "does nothing", check `public/hot` exists.
- Browser checks were driven with the bundled Playwright, run **from the project
  root** so `playwright` resolves. WebAuthn needs a CDP virtual authenticator:
  ```js
  const cdp = await ctx.newCDPSession(page)
  await cdp.send('WebAuthn.enable')
  await cdp.send('WebAuthn.addVirtualAuthenticator', { options: {
      protocol:'ctap2', transport:'internal', hasResidentKey:true,
      hasUserVerification:true, isUserVerified:true, automaticPresenceSimulation:true } })
  ```
- TOTP flows were verified by computing a real RFC 6238 code from the secret the
  dialog displays (base32 → HMAC-SHA1 → dynamic truncation) — no mocking.
- `pint` on whole directories reformats ~98 unrelated files. Pass explicit paths.
- `php artisan tinker <script>` segfaults intermittently; retry, or use `psql`.
