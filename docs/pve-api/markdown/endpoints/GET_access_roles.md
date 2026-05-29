# GET /access/roles

Role index.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "privs": {
        "format": "pve-priv-list",
        "optional": 1,
        "type": "string"
      },
      "roleid": {
        "format": "pve-roleid",
        "type": "string"
      },
      "special": {
        "default": 0,
        "optional": 1,
        "type": "boolean"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{roleid}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Role index.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "privs": {
          "format": "pve-priv-list",
          "optional": 1,
          "type": "string"
        },
        "roleid": {
          "format": "pve-roleid",
          "type": "string"
        },
        "special": {
          "default": 0,
          "optional": 1,
          "type": "boolean"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{roleid}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
