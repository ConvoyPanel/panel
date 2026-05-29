# PUT /access/groups/{groupid}

Update group data.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| groupid | string | yes |  |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
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
  "description": "Update group data.",
  "method": "PUT",
  "name": "update_group",
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
