# DELETE /cluster/firewall/ipset/{name}

Delete IPSet

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | IP set name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| force | boolean | no | Delete all members of the IPSet, if there are any. |

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
  "description": "Delete IPSet",
  "method": "DELETE",
  "name": "delete_ipset",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "force": {
        "description": "Delete all members of the IPSet, if there are any.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "name": {
        "description": "IP set name.",
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
