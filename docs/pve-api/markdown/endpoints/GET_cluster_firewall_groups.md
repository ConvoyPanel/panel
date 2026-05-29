# GET /cluster/firewall/groups

List security groups.

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
      "group": {
        "description": "Security Group name.",
        "maxLength": 18,
        "minLength": 2,
        "pattern": "[A-Za-z][A-Za-z0-9\\-\\_]+",
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{group}",
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
  "description": "List security groups.",
  "method": "GET",
  "name": "list_security_groups",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "user": "all"
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
        "group": {
          "description": "Security Group name.",
          "maxLength": 18,
          "minLength": 2,
          "pattern": "[A-Za-z][A-Za-z0-9\\-\\_]+",
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{group}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
