# GET /access/acl

Get Access Control List (ACLs).

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "additionalProperties": 0,
    "properties": {
      "path": {
        "description": "Access control path",
        "type": "string"
      },
      "propagate": {
        "default": 1,
        "description": "Allow to propagate (inherit) permissions.",
        "optional": 1,
        "type": "boolean"
      },
      "roleid": {
        "type": "string"
      },
      "type": {
        "enum": [
          "user",
          "group",
          "token"
        ],
        "type": "string"
      },
      "ugid": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "type": "array"
}
```

## Permissions

```json
{
  "description": "The returned list is restricted to objects where you have rights to modify permissions.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get Access Control List (ACLs).",
  "method": "GET",
  "name": "read_acl",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "description": "The returned list is restricted to objects where you have rights to modify permissions.",
    "user": "all"
  },
  "returns": {
    "items": {
      "additionalProperties": 0,
      "properties": {
        "path": {
          "description": "Access control path",
          "type": "string"
        },
        "propagate": {
          "default": 1,
          "description": "Allow to propagate (inherit) permissions.",
          "optional": 1,
          "type": "boolean"
        },
        "roleid": {
          "type": "string"
        },
        "type": {
          "enum": [
            "user",
            "group",
            "token"
          ],
          "type": "string"
        },
        "ugid": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
