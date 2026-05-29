# POST /cluster/firewall/ipset

Create new IPSet

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | IP set name. |
| comment | string | no |  |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| rename | string | no | Rename an existing IPSet. You can set 'rename' to the same value as 'name' to update the 'comment' of an existing IPSet. |

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
  "description": "Create new IPSet",
  "method": "POST",
  "name": "create_ipset",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
        "description": "IP set name.",
        "maxLength": 64,
        "minLength": 2,
        "pattern": "[A-Za-z][A-Za-z0-9\\-\\_]+",
        "type": "string"
      },
      "rename": {
        "description": "Rename an existing IPSet. You can set 'rename' to the same value as 'name' to update the 'comment' of an existing IPSet.",
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
