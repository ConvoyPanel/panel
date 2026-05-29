# GET /cluster/notifications/endpoints/webhook

Returns a list of all webhook endpoints

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "body": {
        "description": "HTTP body, base64 encoded",
        "optional": 1,
        "type": "string"
      },
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
      "header": {
        "description": "HTTP headers to set. These have to be formatted as a property string in the format name=<name>,value=<base64 of value>",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "method": {
        "description": "HTTP method",
        "enum": [
          "post",
          "put",
          "get"
        ],
        "type": "string"
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
      "secret": {
        "description": "Secrets to set. These have to be formatted as a property string in the format name=<name>,value=<base64 of value>",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "url": {
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
  "description": "Returns a list of all webhook endpoints",
  "method": "GET",
  "name": "get_webhook_endpoints",
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
        "body": {
          "description": "HTTP body, base64 encoded",
          "optional": 1,
          "type": "string"
        },
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
        "header": {
          "description": "HTTP headers to set. These have to be formatted as a property string in the format name=<name>,value=<base64 of value>",
          "items": {
            "type": "string"
          },
          "optional": 1,
          "type": "array"
        },
        "method": {
          "description": "HTTP method",
          "enum": [
            "post",
            "put",
            "get"
          ],
          "type": "string"
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
        "secret": {
          "description": "Secrets to set. These have to be formatted as a property string in the format name=<name>,value=<base64 of value>",
          "items": {
            "type": "string"
          },
          "optional": 1,
          "type": "array"
        },
        "url": {
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
