# POST /nodes/{node}/lxc/{vmid}/migrate

Migrate the container to another node. Creates a new migration task.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| target | string | yes | Target node. |
| bwlimit | number | no | Override I/O bandwidth limit (in KiB/s). |
| online | boolean | no | Use online/live migration. |
| restart | boolean | no | Use restart migration |
| target-storage | string | no | Mapping from source to target storages. Providing only a single storage ID maps all source storages to that storage. Providing the special value '1' will map each source storage to itself. |
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
  "description": "Migrate the container to another node. Creates a new migration task.",
  "method": "POST",
  "name": "migrate_vm",
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
      "target": {
        "description": "Target node.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "target-storage": {
        "description": "Mapping from source to target storages. Providing only a single storage ID maps all source storages to that storage. Providing the special value '1' will map each source storage to itself.",
        "format": "storage-pair-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
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
