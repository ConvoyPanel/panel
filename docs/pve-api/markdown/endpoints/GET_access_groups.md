# GET /access/groups

Group index.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "comment": {
        "optional": 1,
        "type": "string"
      },
      "groupid": {
        "format": "pve-groupid",
        "type": "string"
      },
      "users": {
        "description": "list of users which form this group",
        "format": "pve-userid-list",
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{groupid}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "The returned list is restricted to groups where you have 'User.Modify', 'Sys.Audit'  or 'Group.Allocate' permissions on /access/groups/<group>.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Group index.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "description": "The returned list is restricted to groups where you have 'User.Modify', 'Sys.Audit'  or 'Group.Allocate' permissions on /access/groups/<group>.",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "comment": {
          "optional": 1,
          "type": "string"
        },
        "groupid": {
          "format": "pve-groupid",
          "type": "string"
        },
        "users": {
          "description": "list of users which form this group",
          "format": "pve-userid-list",
          "optional": 1,
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{groupid}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
