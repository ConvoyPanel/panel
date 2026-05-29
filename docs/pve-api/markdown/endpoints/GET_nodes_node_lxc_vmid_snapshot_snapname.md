# GET /nodes/{node}/lxc/{vmid}/snapshot/{snapname}

snapshot_cmd_idx

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
  "items": {
    "properties": {},
    "type": "object"
  },
  "links": [
    {
      "href": "{cmd}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "",
  "method": "GET",
  "name": "snapshot_cmd_idx",
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
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {},
      "type": "object"
    },
    "links": [
      {
        "href": "{cmd}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
