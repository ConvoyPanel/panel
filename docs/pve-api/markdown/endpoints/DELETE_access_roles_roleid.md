# DELETE /access/roles/{roleid}

Delete role.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| roleid | string | yes |  |

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
  "description": "Delete role.",
  "method": "DELETE",
  "name": "delete_role",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
