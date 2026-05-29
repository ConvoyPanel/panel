# DELETE /cluster/firewall/aliases/{name}

Remove IP or Network alias.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | Alias name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |

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
  "description": "Remove IP or Network alias.",
  "method": "DELETE",
  "name": "remove_alias",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "name": {
        "description": "Alias name.",
        "maxLength": 64,
        "minLength": 2,
        "pattern": "[A-Za-z][A-Za-z0-9\\-\\_]+",
        "type": "string"
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
  "returns": {
    "type": "null"
  }
}
```
