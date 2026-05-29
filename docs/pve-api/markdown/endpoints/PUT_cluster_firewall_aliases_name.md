# PUT /cluster/firewall/aliases/{name}

Update IP or Network alias.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | Alias name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| cidr | string | yes | Network/IP specification in CIDR format. |
| comment | string | no |  |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| rename | string | no | Rename an existing alias. |

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
  "description": "Update IP or Network alias.",
  "method": "PUT",
  "name": "update_alias",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "cidr": {
        "description": "Network/IP specification in CIDR format.",
        "format": "IPorCIDR",
        "type": "string",
        "typetext": "<string>"
      },
      "comment": {
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
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
      },
      "rename": {
        "description": "Rename an existing alias.",
        "maxLength": 64,
        "minLength": 2,
        "optional": 1,
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
