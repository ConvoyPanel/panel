# PUT /pools

Update pool.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| poolid | string | yes |  |
| allow-move | boolean | no | Allow adding a guest even if already in another pool. The guest will be removed from its current pool and added to this one. |
| comment | string | no |  |
| delete | boolean | no | Remove the passed VMIDs and/or storage IDs instead of adding them. |
| storage | string | no | List of storage IDs to add or remove from this pool. |
| vms | string | no | List of guest VMIDs to add or remove from this pool. |

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
  "description": "You also need the right to modify permissions on any object you add/delete."
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Update pool.",
  "method": "PUT",
  "name": "update_pool",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "allow-move": {
        "default": 0,
        "description": "Allow adding a guest even if already in another pool. The guest will be removed from its current pool and added to this one.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "comment": {
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "delete": {
        "default": 0,
        "description": "Remove the passed VMIDs and/or storage IDs instead of adding them.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "poolid": {
        "format": "pve-poolid",
        "type": "string",
        "typetext": "<string>"
      },
      "storage": {
        "description": "List of storage IDs to add or remove from this pool.",
        "format": "pve-storage-id-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "vms": {
        "description": "List of guest VMIDs to add or remove from this pool.",
        "format": "pve-vmid-list",
        "optional": 1,
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
    "description": "You also need the right to modify permissions on any object you add/delete."
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
