# PUT /nodes/{node}/lxc/{vmid}/snapshot/{snapname}/config

Update snapshot metadata.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| snapname | string | yes | The name of the snapshot. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| description | string | no | A textual description or comment. |

## Returns

```json
{
  "type": "null"
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
  "description": "Update snapshot metadata.",
  "method": "PUT",
  "name": "update_snapshot_config",
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
    "type": "null"
  }
}
```
