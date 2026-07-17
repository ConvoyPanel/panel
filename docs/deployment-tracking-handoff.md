# Deployment tracking — review + redesign handoff

Written 2026-07-17. **Temporary** working doc. Records a review of the
install/deploy progress system and the redesign that followed. Part 1 (the
safety core), Part 2 (single-owner jobs), and Part 3 (additive progress polish)
are all **built and green**. Only the optional audit log under "Remaining" is
unbuilt. Delete this file once that lands or is dropped.

**Live-verified (2026-07-17):** the merged delete path was run end to end against
the real Proxmox node (`us-southeast-2`). A throwaway VM was created + started,
`StopVmJob` issued exactly one KILL and completed on the guest reporting stopped,
`DeleteVmJob` issued one destroy and completed, and the VM was confirmed gone.
The clone/install path was **not** run live — the dev node has no template to
clone from; its merge logic is covered by the faked-Proxmox kick-once test.

The prompt that started this: *"the deployment tracking system for installs
kinda sucks… clunky and prone to mistakes if you forget to close out a
deployment step."* That instinct was right, and the schema was **not** the
problem — a parent deployment with child steps carrying status/progress/error is
the correct shape and is what you'd design from scratch. Every defect lived in
one thin layer above it: the step lifecycle was hand-managed inside each queued
job, with no single owner and no enforcement.

## Root cause (what was wrong)

No state machine. A `DeploymentStep` row was mutated freely by whatever job was
running, and start→complete was a ritual each job had to remember. Symptoms:

- **Three ways to complete a step** — `->complete()`, a raw
  `update(['status' => COMPLETED])` in `WaitUntilVmIsCreatedJob`, and a batch
  `->then()` in `DeleteServerAction`.
- **Split ownership** — `clone`, `stop-vm`, and `delete-vm` were each *started*
  by one job and *completed* by another. `SendPowerCommandJob`'s `markComplete`
  flag existed only to encode "do I own the close or not?" — the exact
  forgot-to-close hazard, as a parameter.
- **A real bug** — `SendPowerCommandJob` completed the step in a `finally`, so a
  power command that *threw* still marked its step `COMPLETED`, leaving a
  `FAILED` deployment with a `COMPLETED` step. Nothing guarded transition order.
- **Magic progress** — `configure` hard-coded `progress_total => 3` to match
  three callbacks in `VmSyncService`; the non-install path used a fake `99`;
  `WaitUntilVmIsCreatedJob` grew the total mid-flight so the bar jumped backward.
- **Two sources of truth** — deployment status and server status written in
  separate un-transactioned `update()`s, which the frontend reconciled with a
  *"maybe it finished?"* re-fetch hack.
- **Dead code** — `DeploymentController` was unrouted and its `firstOrFail()`
  had no ordering.
- **Uncapped error text** — `FailsWithStep` wrote the raw provider message into a
  191-char column with no truncation (a latent write failure).

## The principle

**A step's status is derived from the outcome of running it — never narrated by
hand.** Three moving parts, all in Part 1 below:

1. A **guarded state machine** on `DeploymentStep`: `markRunning` /
   `markCompleted` / `markFailed`, where every illegal or repeated transition is
   a silent no-op. `COMPLETED`/`FAILED` are terminal.
2. A **`run(callable)`** wrapper that composes the transitions into the common
   "do work, then done" shape so a one-shot job *cannot* forget to close its
   step. Completion is tied to the work returning without throwing.
3. Steps whose work genuinely spans two jobs (kick then poll) call the guarded
   transitions directly — the guards make the split safe even though it's split.

Why not computed status or event sourcing (asked during design):

- **Computed** ("done iff all steps done") is wrong here because the step set
  isn't closed at read time — a deployment with zero steps yet reads as
  vacuously complete, and lazily-added steps create false-complete windows. The
  authority on "done" is the **orchestrator** (`Bus::chain` reaching its last
  link), not a count of steps. Keep status **stored**, written by the lifecycle.
- **Event sourcing** would make illegal transitions structurally impossible, but
  live 250ms polling forces a cached projection (a stored status again), it adds
  real machinery, and it still needs the chain to emit "completed." The state
  machine buys ~90% of the safety for ~10% of the cost. Its one unique win — an
  audit timeline — is noted as an optional add-on in Part 2.

**Eager step creation is now an invariant**, not an accident: every step row is
inserted before `Bus::chain(...)->dispatch()`, so the UI shows the full
checklist immediately and rows only ever change state — they never pop in. This
is what the "weird if it keeps adding rows" concern was about; keep it.

## Part 1 — BUILT (green: `tests/Unit/Models/DeploymentStepLifecycleTest.php`, 7 cases; full unit + server/services/console suites pass)

- `DeploymentStatus::isTerminal()` — `app/Enums/Server/DeploymentStatus.php`.
- `DeploymentStep` — replaced `start()`/`complete()` with guarded `markRunning`,
  `markCompleted`, `markFailed(?Throwable)` (truncates the message to fit the
  column), `advance()`, and `run()`. `app/Models/DeploymentStep.php`.
- `FailsWithStep` now delegates to `markFailed` — it's the retry-exhaustion
  backstop, and the guard means a step that already succeeded stays succeeded.
- One-shot jobs use `run()`: `ConfigureVmJob`, `UpdatePasswordJob`.
- **`finally` bug fixed** — `SendPowerCommandJob` completes only on success; a
  thrown command propagates and the step never falsely completes.
- Kick jobs (`BuildServerJob`, `DeleteServerJob`) use `markRunning`; poll jobs
  (`WaitUntilVmIsCreatedJob`, `WaitUntilVmIsDeletedJob`, `MonitorStateJob`) use
  `markCompleted`.
- Clone progress no longer jumps backward — the poll adopts Proxmox's
  authoritative total and clamps current to it.
- Status writes transacted — `ManagesDeploymentLifecycle::onComplete/onFail`
  wrap the deployment+server updates in `DB::transaction`.
- Deleted the dead `DeploymentController`.

Net effect: illegal transitions can't corrupt state, one-shot jobs can't forget
to close, and the one concrete correctness bug is gone — without touching the
schema.

## Part 2 — BUILT (single-owner jobs; green: `tests/Feature/Servers/DeploymentJobLifecycleTest.php`, 5 cases)

The three split steps (`clone`, `stop-vm`, `delete-vm`) each now have **one job
that owns the whole step** — it starts the Proxmox task once, then polls the same
task to completion by releasing itself. The `markComplete` flag and the split
ownership are gone.

The key that made the merge safe (and that my earlier "this needs careful state"
note was pointing at): **guard the kick on a durable marker, not on status.** A
new nullable `deployment_steps.task_upid` column records the task's UPID the
moment it's issued; `DeploymentStep::kickOnce()` starts the task only when
task_upid is null, so a released/retried run resumes polling instead of issuing
the command twice. Status flips to RUNNING *before* the remote call returns, so
guarding on status would re-kick after a failed start — the UPID marker doesn't.
Unified retry policy is `retryUntil` for the whole job (kick failures retry
within the same wall-clock budget); no more `tries`-vs-`retryUntil` split.

- `CloneVmJob` replaces `BuildServerJob` + `WaitUntilVmIsCreatedJob`.
- `StopVmJob` replaces `SendPowerCommandJob(KILL)` + `MonitorStateJob`.
- `DeleteVmJob` replaces `DeleteServerJob` + `WaitUntilVmIsDeletedJob`.
- `SendPowerCommandJob` kept for the fully-owned `start-vm` step, with the
  `markComplete` flag removed (it always completes now).
- `ServerBuildService::delete()` returns its UPID (was `void`); an already-gone
  VM surfaces as a nonexistent-VM error the job treats as already-deleted.
- Tests pin the actual risk: `build`/`send`/`delete` are asserted to run
  **exactly once** across two `handle()` calls, plus the already-gone paths.

The `clone` broad-catch during progress polling is intentional ("task status not
readable yet"); a genuine error still loops to the 30-minute `retryUntil` rather
than failing fast. Narrow it once the exceptions `getCloneProgress` throws are
pinned down.

## Part 3 — BUILT (additive progress polish; migration `..._add_progress_polish_to_deployments`)

- `deployment_steps.progress_mode` (`determinate` | `indeterminate`), a new
  `ProgressMode` enum, cast on the model, carried through `DeploymentStepData`
  and the hand-written `features/servers/types.ts`. `DeploymentStepRow` now draws
  the bar from `progressMode` instead of a hardcoded per-name map. `configure`
  is **indeterminate** (the chosen option — the fake `3`/`99` totals are gone and
  `ConfigureVmJob` no longer passes a progress callback); `clone` and
  `delete-backups` stay determinate with real totals; the rest are indeterminate.
- `deployment_steps.sequence`, stamped by a new `Deployment::addSteps()` helper
  that every step-creating site now uses. The `steps()` relation orders by
  `sequence` then `id`, so display order is explicit and stays correct even for a
  reinstall (delete steps then build steps). `sequence` is exposed in the DTO.
- `deployments.started_at`, set by `onStart`; `PruneDeploymentsCommand` now times
  staleness from `COALESCE(started_at, requested_at)`, so a queue backlog no
  longer trips the stuck timer before a deployment has begun running.

Note on the `configure` count: `indeterminate` was chosen, so the coupling is
moot. If a determinate bar is ever wanted back, the honest fix is to pass a
**label** to `VmSyncService`'s `$onProgress('…')` (the three calls are already
discrete named operations) so progress is named sub-steps and the total is just
`count(labels)` — never a magic number duplicated in the action.

## Remaining — optional, not built

**Audit log** — have `run()`/the transitions also append to a `deployment_events`
table purely as a debugging timeline, never the read source. Only worth it if
stuck-install forensics become a recurring need.

**Live clone/install run** — not yet possible on the dev node (no template).
Needs a template VM on Proxmox plus the allocation/storage setup a real install
expects; the job logic is covered by tests in the meantime.

## Sandbox note

`ddev` wouldn't start here until `convoy.ddev.site` resolved — `/etc/hosts` is
read-only, so I bind-mounted a writable copy with the entry:
`cp /etc/hosts /tmp/hosts.new && printf '127.0.0.1 convoy.ddev.site\n' >> /tmp/hosts.new && sudo mount --bind /tmp/hosts.new /etc/hosts`.
Then `ddev start` / `ddev exec php artisan test …` work.
