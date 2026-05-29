# POST /nodes/{node}/qemu/{vmid}/snapshot

Snapshot a VM.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| snapname | string | yes | The name of the snapshot. |
| description | string | no | A textual description or comment. |
| vmstate | boolean | no | Save the vmstate |

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
      "VM.Snapshot"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Snapshot a VM.",
  "method": "POST",
  "name": "snapshot",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "description": {
        "description": "A textual description or comment.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
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
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      },
      "vmstate": {
        "description": "Save the vmstate",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/vms/{vmid}",
      [
        "VM.Snapshot"
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
