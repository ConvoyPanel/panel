# GET /nodes/{node}/disks/smart

Get SMART Health of a disk.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| disk | string | yes | Block device name |
| healthonly | boolean | no | If true returns only the health status |

## Returns

```json
{
  "properties": {
    "attributes": {
      "optional": 1,
      "type": "array"
    },
    "health": {
      "type": "string"
    },
    "text": {
      "optional": 1,
      "type": "string"
    },
    "type": {
      "optional": 1,
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
  "description": "Get SMART Health of a disk.",
  "method": "GET",
  "name": "smart",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "disk": {
        "description": "Block device name",
        "pattern": "^/dev/[a-zA-Z0-9\\/]+$",
        "type": "string"
      },
      "healthonly": {
        "description": "If true returns only the health status",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
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
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "attributes": {
        "optional": 1,
        "type": "array"
      },
      "health": {
        "type": "string"
      },
      "text": {
        "optional": 1,
        "type": "string"
      },
      "type": {
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
