# GET /nodes/{node}/lxc/{vmid}/snapshot/{snapname}/config

Get snapshot configuration

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| snapname | string | yes | The name of the snapshot. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

None.

## Returns

```json
{
  "type": "object"
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
      "VM.Snapshot.Rollback",
      "VM.Audit"
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
  "description": "Get snapshot configuration",
  "method": "GET",
  "name": "get_snapshot_config",
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
        "VM.Snapshot.Rollback",
        "VM.Audit"
      ],
      "any",
      1
    ]
  },
  "proxyto": "node",
  "returns": {
    "type": "object"
  }
}
```
