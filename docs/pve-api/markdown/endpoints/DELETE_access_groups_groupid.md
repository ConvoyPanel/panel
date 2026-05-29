# DELETE /access/groups/{groupid}

Delete group.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| groupid | string | yes |  |

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
  "description": "Delete group.",
  "method": "DELETE",
  "name": "delete_group",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
