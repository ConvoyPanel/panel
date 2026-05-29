# DELETE /nodes/{node}/ceph/mon/{monid}

Destroy a Ceph Monitor. Refuses to remove the last monitor of the cluster. Does not destroy any Manager on the same node; use /nodes/{node}/ceph/mgr/{id} for that.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| monid | string | yes | Monitor ID |
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "type": "string"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Destroy a Ceph Monitor. Refuses to remove the last monitor of the cluster. Does not destroy any Manager on the same node; use /nodes/{node}/ceph/mgr/{id} for that.",
  "method": "DELETE",
  "name": "destroymon",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "monid": {
        "description": "Monitor ID",
        "pattern": "[a-zA-Z0-9]([a-zA-Z0-9\\-]*[a-zA-Z0-9])?",
        "type": "string"
      },
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
      "/",
      [
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
