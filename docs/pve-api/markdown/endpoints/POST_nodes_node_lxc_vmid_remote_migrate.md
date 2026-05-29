# POST /nodes/{node}/lxc/{vmid}/remote_migrate

Migrate the container to another cluster. Creates a new migration task. EXPERIMENTAL feature!

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
| bwlimit | number | no | Override I/O bandwidth limit (in KiB/s). |
| delete | boolean | no | Delete the original CT and related data after successful migration. By default the original CT is kept on the source cluster in a stopped state. |
| online | boolean | no | Use online/live migration. |
| restart | boolean | no | Use restart migration |
| target-vmid | integer | no | The (unique) ID of the VM. |
| timeout | integer | no | Timeout in seconds for shutdown for restart migration |

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
  "description": "Migrate the container to another cluster. Creates a new migration task. EXPERIMENTAL feature!",
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
        "type": "number",
        "typetext": "<number> (0 - N)"
      },
      "delete": {
        "default": 0,
        "description": "Delete the original CT and related data after successful migration. By default the original CT is kept on the source cluster in a stopped state.",
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
        "description": "Use online/live migration.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "restart": {
        "description": "Use restart migration",
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
      "timeout": {
        "default": 180,
        "description": "Timeout in seconds for shutdown for restart migration",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
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
