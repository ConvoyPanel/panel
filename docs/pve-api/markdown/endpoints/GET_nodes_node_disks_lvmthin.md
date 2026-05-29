# GET /nodes/{node}/disks/lvmthin

List LVM thinpools

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "lv": {
        "description": "The name of the thinpool.",
        "type": "string"
      },
      "lv_size": {
        "description": "The size of the thinpool in bytes.",
        "type": "integer"
      },
      "metadata_size": {
        "description": "The size of the metadata lv in bytes.",
        "type": "integer"
      },
      "metadata_used": {
        "description": "The used bytes of the metadata lv.",
        "type": "integer"
      },
      "used": {
        "description": "The used bytes of the thinpool.",
        "type": "integer"
      },
      "vg": {
        "description": "The associated volume group.",
        "type": "string"
      }
    },
    "type": "object"
  },
  "type": "array"
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
  "description": "List LVM thinpools",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
    "items": {
      "properties": {
        "lv": {
          "description": "The name of the thinpool.",
          "type": "string"
        },
        "lv_size": {
          "description": "The size of the thinpool in bytes.",
          "type": "integer"
        },
        "metadata_size": {
          "description": "The size of the metadata lv in bytes.",
          "type": "integer"
        },
        "metadata_used": {
          "description": "The used bytes of the metadata lv.",
          "type": "integer"
        },
        "used": {
          "description": "The used bytes of the thinpool.",
          "type": "integer"
        },
        "vg": {
          "description": "The associated volume group.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
