# DELETE /nodes/{node}/ceph/pool/{name}

Destroy pool

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | The name of the pool. It must be unique. |
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| force | boolean | no | If true, destroys pool even if in use |
| remove_ecprofile | boolean | no | Remove the erasure code profile. Defaults to true, if applicable. |
| remove_storages | boolean | no | Remove all pveceph-managed storages configured for this pool |

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
  "description": "Destroy pool",
  "method": "DELETE",
  "name": "destroypool",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "force": {
        "default": 0,
        "description": "If true, destroys pool even if in use",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "name": {
        "description": "The name of the pool. It must be unique.",
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "remove_ecprofile": {
        "default": 1,
        "description": "Remove the erasure code profile. Defaults to true, if applicable.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "remove_storages": {
        "default": 0,
        "description": "Remove all pveceph-managed storages configured for this pool",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
