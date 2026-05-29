# GET /cluster/firewall/ipset

List IPSets

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
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 0,
        "type": "string"
      },
      "name": {
        "description": "IP set name.",
        "maxLength": 64,
        "minLength": 2,
        "pattern": "[A-Za-z][A-Za-z0-9\\-\\_]+",
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{name}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List IPSets",
  "method": "GET",
  "name": "ipset_index",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit"
      ]
    ]
  },
  "returns": {
    "items": {
      "properties": {
        "comment": {
          "optional": 1,
          "type": "string"
        },
        "digest": {
          "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
          "maxLength": 64,
          "optional": 0,
          "type": "string"
        },
        "name": {
          "description": "IP set name.",
          "maxLength": 64,
          "minLength": 2,
          "pattern": "[A-Za-z][A-Za-z0-9\\-\\_]+",
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{name}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
