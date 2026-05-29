# POST /cluster/notifications/endpoints/webhook

Create a new webhook endpoint

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| method | string | yes | HTTP method |
| name | string | yes | The name of the endpoint. |
| url | string | yes | Server URL |
| body | string | no | HTTP body, base64 encoded |
| comment | string | no | Comment |
| disable | boolean | no | Disable this target |
| header | array | no | HTTP headers to set. These have to be formatted as a property string in the format name=<name>,value=<base64 of value> |
| secret | array | no | Secrets to set. These have to be formatted as a property string in the format name=<name>,value=<base64 of value> |

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
    "and",
    [
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Modify"
      ]
    ],
    [
      "or",
      [
        "perm",
        "/",
        [
          "Sys.Audit",
          "Sys.Modify"
        ]
      ],
      [
        "perm",
        "/",
        [
          "Sys.AccessNetwork"
        ]
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Create a new webhook endpoint",
  "method": "POST",
  "name": "create_webhook_endpoint",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "body": {
        "description": "HTTP body, base64 encoded",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "comment": {
        "description": "Comment",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "disable": {
        "default": 0,
        "description": "Disable this target",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "header": {
        "description": "HTTP headers to set. These have to be formatted as a property string in the format name=<name>,value=<base64 of value>",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
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
        "type": "string",
        "typetext": "<string>"
      },
      "secret": {
        "description": "Secrets to set. These have to be formatted as a property string in the format name=<name>,value=<base64 of value>",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      },
      "url": {
        "description": "Server URL",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "and",
      [
        "perm",
        "/mapping/notifications",
        [
          "Mapping.Modify"
        ]
      ],
      [
        "or",
        [
          "perm",
          "/",
          [
            "Sys.Audit",
            "Sys.Modify"
          ]
        ],
        [
          "perm",
          "/",
          [
            "Sys.AccessNetwork"
          ]
        ]
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
