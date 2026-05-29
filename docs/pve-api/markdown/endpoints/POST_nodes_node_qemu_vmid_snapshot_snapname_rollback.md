# POST /nodes/{node}/qemu/{vmid}/snapshot/{snapname}/rollback

Rollback VM state to specified snapshot.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| snapname | string | yes | The name of the snapshot. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| start | boolean | no | Whether the VM should get started after rolling back successfully. (Note: VMs will be automatically started if the snapshot includes RAM.) |

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
      "VM.Snapshot",
      "VM.Snapshot.Rollback"
    ],
    "any",
    1
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Rollback VM state to specified snapshot.",
  "method": "POST",
  "name": "rollback",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "snapname": {
        "description": "The name of the snapshot.",
        "format": "pve-configid",
        "maxLength": 40,
        "type": "string",
        "typetext": "<string>"
      },
      "start": {
        "default": 0,
        "description": "Whether the VM should get started after rolling back successfully. (Note: VMs will be automatically started if the snapshot includes RAM.)",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
        "VM.Snapshot",
        "VM.Snapshot.Rollback"
      ],
      "any",
      1
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
