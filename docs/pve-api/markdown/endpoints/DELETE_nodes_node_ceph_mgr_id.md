# DELETE /nodes/{node}/ceph/mgr/{id}

Destroy Ceph Manager.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The ID of the manager |
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
  "description": "Destroy Ceph Manager.",
  "method": "DELETE",
  "name": "destroymgr",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
        "description": "The ID of the manager",
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
