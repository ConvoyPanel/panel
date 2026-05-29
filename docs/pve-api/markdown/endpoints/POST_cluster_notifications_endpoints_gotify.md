# POST /cluster/notifications/endpoints/gotify

Create a new gotify endpoint

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | The name of the endpoint. |
| server | string | yes | Server URL |
| token | string | yes | Secret token |
| comment | string | no | Comment |
| disable | boolean | no | Disable this target |

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
  "description": "Create a new gotify endpoint",
  "method": "POST",
  "name": "create_gotify_endpoint",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
      "name": {
        "description": "The name of the endpoint.",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      },
      "server": {
        "description": "Server URL",
        "type": "string",
        "typetext": "<string>"
      },
      "token": {
        "description": "Secret token",
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
