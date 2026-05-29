# DELETE /access/domains/{realm}

Delete an authentication server.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| realm | string | yes | Authentication domain ID |

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
    "/access/realm",
    [
      "Realm.Allocate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Delete an authentication server.",
  "method": "DELETE",
  "name": "delete",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "realm": {
        "description": "Authentication domain ID",
        "format": "pve-realm",
        "maxLength": 32,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/access/realm",
      [
        "Realm.Allocate"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
