# DELETE /storage/{storage}

Delete storage configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| storage | string | yes | The storage identifier. |

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
    "/storage",
    [
      "Datastore.Allocate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Delete storage configuration.",
  "method": "DELETE",
  "name": "delete",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "storage": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/storage",
      [
        "Datastore.Allocate"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
