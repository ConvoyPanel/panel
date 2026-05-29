# POST /nodes/{node}/qemu/{vmid}/remote_migrate

Migrate virtual machine to a remote cluster. Creates a new migration task. EXPERIMENTAL feature!

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| target-bridge | string | yes | Mapping from source to target bridges. Providing only a single bridge ID maps all source bridges to that bridge. Providing the special value '1' will map each source bridge to itself. |
| target-endpoint | string | yes | Remote target endpoint |
| target-storage | string | yes | Mapping from source to target storages. Providing only a single storage ID maps all source storages to that storage. Providing the special value '1' will map each source storage to itself. |
| bwlimit | integer | no | Override I/O bandwidth limit (in KiB/s). |
| delete | boolean | no | Delete the original VM and related data after successful migration. By default the original VM is kept on the source cluster in a stopped state. |
| online | boolean | no | Use online/live migration if VM is running. Ignored if VM is stopped. |
| target-vmid | integer | no | The (unique) ID of the VM. |

## Returns

```json
{
  "description": "the task ID.",
  "type": "string"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/vms/{vmid}",
    [
      "VM.Migrate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Migrate virtual machine to a remote cluster. Creates a new migration task. EXPERIMENTAL feature!",
  "method": "POST",
  "name": "remote_migrate_vm",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "bwlimit": {
        "default": "migrate limit from datacenter or storage config",
        "description": "Override I/O bandwidth limit (in KiB/s).",
        "minimum": "0",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "delete": {
        "default": 0,
        "description": "Delete the original VM and related data after successful migration. By default the original VM is kept on the source cluster in a stopped state.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "online": {
        "description": "Use online/live migration if VM is running. Ignored if VM is stopped.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "target-bridge": {
        "description": "Mapping from source to target bridges. Providing only a single bridge ID maps all source bridges to that bridge. Providing the special value '1' will map each source bridge to itself.",
        "format": "bridge-pair-list",
        "type": "string",
        "typetext": "<string>"
      },
      "target-endpoint": {
        "description": "Remote target endpoint",
        "format": "proxmox-remote",
        "type": "string",
        "typetext": "apitoken=<PVEAPIToken=user@realm!token=SECRET> ,host=<ADDRESS> [,fingerprint=<FINGERPRINT>] [,port=<PORT>]"
      },
      "target-storage": {
        "description": "Mapping from source to target storages. Providing only a single storage ID maps all source storages to that storage. Providing the special value '1' will map each source storage to itself.",
        "format": "storage-pair-list",
        "optional": 0,
        "type": "string",
        "typetext": "<string>"
      },
      "target-vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/vms/{vmid}",
      [
        "VM.Migrate"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "description": "the task ID.",
    "type": "string"
  }
}
```
