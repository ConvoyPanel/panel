# GET /cluster/notifications/endpoints/gotify/{name}

Return a specific gotify endpoint

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
    "name": {
      "description": "The name of the endpoint.",
      "format": "pve-configid",
      "type": "string"
    },
    "server": {
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
  "description": "Return a specific gotify endpoint",
  "method": "GET",
  "name": "get_gotify_endpoint",
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
      "name": {
        "description": "The name of the endpoint.",
        "format": "pve-configid",
        "type": "string"
      },
      "server": {
        "description": "Server URL",
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
