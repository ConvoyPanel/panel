# DELETE /pools/{poolid}

Delete pool (deprecated, no support for nested pools, use 'DELETE /pools/?poolid={poolid}').

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| poolid | string | yes |  |

## Request parameters

None.

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
    "/pool/{poolid}",
    [
      "Pool.Allocate"
    ]
  ],
  "description": "You can only delete empty pools (no members)."
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Delete pool (deprecated, no support for nested pools, use 'DELETE /pools/?poolid={poolid}').",
  "method": "DELETE",
  "name": "delete_pool_deprecated",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "poolid": {
        "format": "pve-poolid",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/pool/{poolid}",
      [
        "Pool.Allocate"
      ]
    ],
    "description": "You can only delete empty pools (no members)."
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
