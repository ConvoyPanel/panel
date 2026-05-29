# PUT /access/roles/{roleid}

Update an existing role.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| roleid | string | yes |  |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| append | boolean | no |  |
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
  "description": "Update an existing role.",
  "method": "PUT",
  "name": "update_role",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "append": {
        "optional": 1,
        "requires": "privs",
        "type": "boolean",
        "typetext": "<boolean>"
      },
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
