# Provisioner snippets over Anchor

The wire contract between the panel and the Anchor agent for provisioner
scripts (#188). Both repos build against this file.

## Why Anchor is involved at all

Proxmox reads custom cloud-init (`cicustom`) only from a file on a storage with
the `snippets` content type, and its API has no way to upload one. Anchor
already runs as root on the node, so it writes the file and deletes it again.
The panel decides everything else: what goes in the file, which storage it
lands on, and when it is removed.

## Lifecycle

```
panel ──Write{storage,name,sha256} + body─▶ anchor    file written, 0600, atomically
panel ──qm set cicustom vendor=<volume>──▶ Proxmox    then the guest starts
guest ──POST /api/provisioning/{run}─────▶ panel      scripts finished (or a timeout)
panel ──qm set --delete cicustom─────────▶ Proxmox
panel ──Delete{storage,name}─────────────▶ anchor     idempotent
```

`cicustom` is removed **before** the file is deleted. A guest whose config
still points at a missing snippet cannot start, so the other order would break
the server for the length of the gap.

## Capability

`provisioning.snippets`, in `/api/v1/info` and the heartbeat. Advertised when
`agent.snippets_enabled` is true, which is the default. The panel refuses to
build a server with provisioners on a node whose agent does not report it.

## Token

A separate claim set, keyed `snippet`, in the same HS256 envelope as the
others (audience = installation id, `protocol` = 1, short expiry). The work
order is inside the token, so a captured token can write only the bytes it was
minted for, to the one file it names.

```jsonc
// write
{ "action": "write", "storage": "local", "name": "convoy-provisioner-<uuid>.yaml", "sha256": "<hex>" }
// delete
{ "action": "delete", "storage": "local", "name": "convoy-provisioner-<uuid>.yaml" }
```

## Endpoints

### `PUT /api/v1/snippets/{name}`

Body: the snippet, as raw bytes. At most 1 MiB.

- `{name}` must equal the claim's `name`, and the claim's action must be
  `write`. Otherwise `401`.
- `name` must match `^convoy-[a-z0-9][a-z0-9-]{0,95}\.yaml$`. Anchor only ever
  touches files the panel named this way, never an admin's own snippets.
  Otherwise `400`.
- `storage` must be a valid PVE storage id, and the storage must be active on
  this node with the `snippets` content type enabled. Otherwise `409`.
- The body's SHA-256 must equal the claim's `sha256`. Otherwise `400`.
- The file lands in that storage's snippets directory (as `pvesm path
  <storage>:snippets/<name>` resolves it), written to a temporary file in the
  same directory, mode `0600`, then renamed over the target. An existing file
  of the same name is replaced.

`200`:

```json
{ "volume": "local:snippets/convoy-provisioner-<uuid>.yaml", "sha256": "<hex>", "size": 1234 }
```

### `DELETE /api/v1/snippets/{name}`

Same name, claim and storage checks, with action `delete`. A file that is
already gone is a success, so the panel can retry a cleanup without special
cases.

`200`:

```json
{ "deleted": true }
```

`deleted` is false when there was nothing to remove.

### Errors

`{ "error": "<message>" }`, as elsewhere. `409` when snippets are switched off
on this agent or the storage cannot hold them, `413` when the body is too big.

## What the snippet contains

Panel-generated `#cloud-config` vendor data. Anchor never parses it. It holds:

- `write_files`: each provisioner script, a fixed runner, the environment for
  the scripts, and the callback the runner reports to. Everything is base64
  encoded, so nothing a customer typed is ever YAML or shell syntax.
- `runcmd`: the runner, which cloud-init runs once per instance.

Proxmox keeps generating the user data (account, password, SSH keys,
`ciupgrade`) and the network config from the guest's own settings. Only the
vendor data is ours. Proxmox derives the instance id from the user data and the
network config, so adding or removing `cicustom vendor=` does not make
cloud-init treat the guest as a new instance.

Field values are secrets as far as Anchor is concerned: the file is `0600` and
nothing logs its contents.
