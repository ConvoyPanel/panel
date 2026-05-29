# GET /access/groups/{groupid}

Get group configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| groupid | string | yes |  |

## Request parameters

None.

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "comment": {
      "optional": 1,
      "type": "string"
    },
    "members": {
      "items": {
        "description": "Full User ID, in the `name@realm` format.",
        "format": "pve-userid",
        "maxLength": 64,
        "type": "string"
      },
      "type": "array"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/access/groups",
    [
      "Sys.Audit",
      "Group.Allocate"
    ],
    "any",
    1
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get group configuration.",
  "method": "GET",
  "name": "read_group",
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
        "Sys.Audit",
        "Group.Allocate"
      ],
      "any",
      1
    ]
  },
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "comment": {
        "optional": 1,
        "type": "string"
      },
      "members": {
        "items": {
          "description": "Full User ID, in the `name@realm` format.",
          "format": "pve-userid",
          "maxLength": 64,
          "type": "string"
        },
        "type": "array"
      }
    },
    "type": "object"
  }
}
```
