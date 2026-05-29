# DELETE /cluster/firewall/groups/{group}

Delete security group.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| group | string | yes | Security Group name. |

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
  "description": "Delete security group.",
  "method": "DELETE",
  "name": "delete_security_group",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "group": {
        "description": "Security Group name.",
        "maxLength": 18,
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
