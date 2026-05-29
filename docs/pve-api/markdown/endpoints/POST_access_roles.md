# POST /access/roles

Create new role.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| roleid | string | yes |  |
| privs | string | no |  |

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
    "/access",
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
  "description": "Create new role.",
  "method": "POST",
  "name": "create_role",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "privs": {
        "format": "pve-priv-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "roleid": {
        "format": "pve-roleid",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/access",
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
