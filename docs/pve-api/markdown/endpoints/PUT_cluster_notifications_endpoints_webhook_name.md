# PUT /cluster/notifications/endpoints/webhook/{name}

Update existing webhook endpoint

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | The name of the endpoint. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| body | string | no | HTTP body, base64 encoded |
| comment | string | no | Comment |
| delete | array | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| disable | boolean | no | Disable this target |
| header | array | no | HTTP headers to set. These have to be formatted as a property string in the format name=<name>,value=<base64 of value> |
| method | string | no | HTTP method |
| secret | array | no | Secrets to set. These have to be formatted as a property string in the format name=<name>,value=<base64 of value> |
| url | string | no | Server URL |

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
  "description": "Update existing webhook endpoint",
  "method": "PUT",
  "name": "update_webhook_endpoint",
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
      "delete": {
        "description": "A list of settings you want to delete.",
        "items": {
          "format": "pve-configid",
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
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
        "optional": 1,
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
        "optional": 1,
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
