# DELETE /nodes/{node}/ceph/mds/{name}

Destroy Ceph Metadata Server

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | The name (ID) of the mds |
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
  "description": "Destroy Ceph Metadata Server",
  "method": "DELETE",
  "name": "destroymds",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "description": "The name (ID) of the mds",
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
