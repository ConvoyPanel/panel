# POST /access/groups

Create new group.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| groupid | string | yes |  |
| comment | string | no |  |

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
    "/access/groups",
    [
      "Group.Allocate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Create new group.",
  "method": "POST",
  "name": "create_group",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "comment": {
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "groupid": {
        "format": "pve-groupid",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/access/groups",
      [
        "Group.Allocate"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
