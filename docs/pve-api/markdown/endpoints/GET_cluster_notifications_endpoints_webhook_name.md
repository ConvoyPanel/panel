# GET /cluster/notifications/endpoints/webhook/{name}

Return a specific webhook endpoint

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | Name of the endpoint. |

## Request parameters

None.

## Returns

```json
{
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
    "digest": {
      "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
      "maxLength": 64,
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
}
```

## Permissions

```json
{
  "check": [
    "or",
    [
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Modify"
      ]
    ],
    [
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Audit"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Return a specific webhook endpoint",
  "method": "GET",
  "name": "get_webhook_endpoint",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "description": "Name of the endpoint.",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "or",
      [
        "perm",
        "/mapping/notifications",
        [
          "Mapping.Modify"
        ]
      ],
      [
        "perm",
        "/mapping/notifications",
        [
          "Mapping.Audit"
        ]
      ]
    ]
  },
  "protected": 1,
  "returns": {
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
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
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
  }
}
```
