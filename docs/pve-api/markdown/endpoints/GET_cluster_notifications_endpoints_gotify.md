# GET /cluster/notifications/endpoints/gotify

Returns a list of all gotify endpoints

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
        "description": "Comment",
        "optional": 1,
        "type": "string"
      },
      "disable": {
        "default": 0,
        "description": "Disable this target",
        "optional": 1,
        "type": "boolean"
      },
      "name": {
        "description": "The name of the endpoint.",
        "format": "pve-configid",
        "type": "string"
      },
      "origin": {
        "description": "Show if this entry was created by a user or was built-in",
        "enum": [
          "user-created",
          "builtin",
          "modified-builtin"
        ],
        "type": "string"
      },
      "server": {
        "description": "Server URL",
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
    "/mapping/notifications",
    [
      "Mapping.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Returns a list of all gotify endpoints",
  "method": "GET",
  "name": "get_gotify_endpoints",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "check": [
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Audit"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "items": {
      "properties": {
        "comment": {
          "description": "Comment",
          "optional": 1,
          "type": "string"
        },
        "disable": {
          "default": 0,
          "description": "Disable this target",
          "optional": 1,
          "type": "boolean"
        },
        "name": {
          "description": "The name of the endpoint.",
          "format": "pve-configid",
          "type": "string"
        },
        "origin": {
          "description": "Show if this entry was created by a user or was built-in",
          "enum": [
            "user-created",
            "builtin",
            "modified-builtin"
          ],
          "type": "string"
        },
        "server": {
          "description": "Server URL",
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
