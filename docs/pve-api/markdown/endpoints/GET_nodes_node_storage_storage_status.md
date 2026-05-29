# GET /nodes/{node}/storage/{storage}/status

Read storage status.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| storage | string | yes | The storage identifier. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "active": {
      "description": "Set when storage is accessible.",
      "optional": 1,
      "type": "boolean"
    },
    "avail": {
      "description": "Available storage space in bytes.",
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    },
    "content": {
      "description": "Allowed storage content types.",
      "format": "pve-storage-content-list",
      "type": "string"
    },
    "enabled": {
      "description": "Set when storage is enabled (not disabled).",
      "optional": 1,
      "type": "boolean"
    },
    "shared": {
      "description": "Shared flag from storage configuration.",
      "optional": 1,
      "type": "boolean"
    },
    "total": {
      "description": "Total storage space in bytes.",
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    },
    "type": {
      "description": "Storage type.",
      "type": "string"
    },
    "used": {
      "description": "Used storage space in bytes.",
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/storage/{storage}",
    [
      "Datastore.Audit",
      "Datastore.AllocateSpace"
    ],
    "any",
    1
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read storage status.",
  "method": "GET",
  "name": "read_status",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "storage": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/storage/{storage}",
      [
        "Datastore.Audit",
        "Datastore.AllocateSpace"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "active": {
        "description": "Set when storage is accessible.",
        "optional": 1,
        "type": "boolean"
      },
      "avail": {
        "description": "Available storage space in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "content": {
        "description": "Allowed storage content types.",
        "format": "pve-storage-content-list",
        "type": "string"
      },
      "enabled": {
        "description": "Set when storage is enabled (not disabled).",
        "optional": 1,
        "type": "boolean"
      },
      "shared": {
        "description": "Shared flag from storage configuration.",
        "optional": 1,
        "type": "boolean"
      },
      "total": {
        "description": "Total storage space in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "type": {
        "description": "Storage type.",
        "type": "string"
      },
      "used": {
        "description": "Used storage space in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      }
    },
    "type": "object"
  }
}
```
