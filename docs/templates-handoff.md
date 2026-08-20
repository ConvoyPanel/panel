# Templates — handoff

**Status: shelved before build, 2026-08-20.** The template-import feature was
removed from the tree in the commit that rewrote this file; nothing
template-shaped is in flight. This file exists so the revisit does not
re-derive two design rounds. It supersedes `cofoundry-templates-handoff.md`
(deleted the same day; git history has it).

## Why it was shelved

PVE 9 can download a **disk image** by URL and attach it to a VM directly
(`import-from` on a disk, `import` content on storages). That beats the whole
vzdump pipeline this feature was built around — download a multi-GB archive
through an agent, `qmrestore` it, `qm template` it, then keep a template
*guest* placed and updated per node/cluster. With images, PVE does the
download itself and there is **no template guest to place**, which dissolves
the VMID-coordination problem that consumed both design rounds.

Cofoundry currently publishes vzdump archives, so it must be updated to
publish raw disk images (qcow2) first. Revisit after that.

## Where things stand in the panel

Exactly the released v4 model, untouched: a `Template` is a name + panel-wide
`vmid` an admin manages by hand, validated live at deploy time
(`ServerCreationService::getTemplate()` checks `template: 1` on the node), and
cloned by `ProxmoxServerClient::create()`. The only template-adjacent thing
that shipped from this effort is the **storage-layer overhaul** (clusters
identified by CA fingerprint, storages one definition per cluster, per-link
capacity — see the `feat(clusters)` commit), which stands on its own and is
what any future "usable from every node that mounts the pool" query will join
against.

## What was built and thrown away (find it in git history)

- **panel** `feat/templates-cofoundry-import` (`74e4b724`) — registry/catalog/
  import services, `template_installs`, polling job, admin endpoints. Rejected
  model (one panel-wide VMID fanned out per node).
- **anchor** `feat/templates-install` (`7f7b8f4`) — a `templates.install`
  capability: download → verify sha256 → `qmrestore` → `qm template`. Sound
  for what it did, but PVE 9 image import likely removes the need for an agent
  in this feature entirely.
- The instances redesign (this file's previous revision, plus the removal
  commit's parent tree) — `templates`/`template_instances` split, poll-side
  reconciliation, CA-scoped placement. Never finished; removed.

## Ideas worth carrying into the image-based design

- **Template vs placement stay different facts.** What a user picks vs where
  it can deploy from. With images the "placement" may become *which storages
  hold the image* — which is exactly a `storage_to_node` join away, already
  built.
- **Observed, not typed.** The poll already decodes every guest's `template`
  flag from `/cluster/resources`; whatever the new model records should be
  verified (and where possible materialized) from observation, not data entry.
  Operators hate creating rows for things that already exist.
- **A registry is a catalogue, not a concept.** Cofoundry should stay a
  config-default URL; `manual`/`url`/`registry` as a source enum, with
  "update available" meaningful only for the latter two.
- **Adoption over forms.** Surface unclaimed observed templates/images and
  offer one-click adoption instead of a node+vmid form.
