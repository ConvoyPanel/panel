# PUT /access/acl

Update Access Control List (add or remove permissions).

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| path | string | yes | Access control path |
| roles | string | yes | List of roles. |
| delete | boolean | no | Remove permissions (instead of adding it). |
| groups | string | no | List of groups. |
| propagate | boolean | no | Allow to propagate (inherit) permissions. |
| tokens | string | no | List of API tokens. |
| users | string | no | List of users. |

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
    "perm-modify",
    "{path}"
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Update Access Control List (add or remove permissions).",
  "method": "PUT",
  "name": "update_acl",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "delete": {
        "description": "Remove permissions (instead of adding it).",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "groups": {
        "description": "List of groups.",
        "format": "pve-groupid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "path": {
        "description": "Access control path",
        "type": "string",
        "typetext": "<string>"
      },
      "propagate": {
        "default": 1,
        "description": "Allow to propagate (inherit) permissions.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "roles": {
        "description": "List of roles.",
        "format": "pve-roleid-list",
        "type": "string",
        "typetext": "<string>"
      },
      "tokens": {
        "description": "List of API tokens.",
        "format": "pve-tokenid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "users": {
        "description": "List of users.",
        "format": "pve-userid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm-modify",
      "{path}"
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
