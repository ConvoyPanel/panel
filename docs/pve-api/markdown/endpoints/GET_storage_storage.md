# GET /storage/{storage}

Read storage configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| storage | string | yes | The storage identifier. |

## Request parameters

None.

## Returns

```json
{
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/storage/{storage}",
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
  "description": "Read storage configuration.",
  "method": "GET",
  "name": "read",
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
      "/storage/{storage}",
      [
        "Datastore.Allocate"
      ]
    ]
  },
  "returns": {
    "type": "object"
  }
}
```
