# DELETE /pools

Delete pool.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| poolid | string | yes |  |

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
  "description": "Delete pool.",
  "method": "DELETE",
  "name": "delete_pool",
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
