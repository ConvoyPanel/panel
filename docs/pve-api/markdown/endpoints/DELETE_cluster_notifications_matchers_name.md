# DELETE /cluster/notifications/matchers/{name}

Remove matcher

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes |  |

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
    "/mapping/notifications",
    [
      "Mapping.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Remove matcher",
  "method": "DELETE",
  "name": "delete_matcher",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Modify"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
