# GET /nodes/{node}/capabilities/qemu/migration

Get node-specific QEMU migration capabilities of the node. Requires the 'Sys.Audit' permission on '/nodes/<node>'.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "has-dbus-vmstate": {
      "description": "Whether the host supports live-migrating additional VM state via the dbus-vmstate helper.",
      "type": "boolean"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/nodes/{node}",
    [
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get node-specific QEMU migration capabilities of the node. Requires the 'Sys.Audit' permission on '/nodes/<node>'.",
  "method": "GET",
  "name": "capabilities",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Audit"
      ]
    ]
  },
  "proxyto": "node",
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "has-dbus-vmstate": {
        "description": "Whether the host supports live-migrating additional VM state via the dbus-vmstate helper.",
        "type": "boolean"
      }
    },
    "type": "object"
  }
}
```
