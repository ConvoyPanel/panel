# GET /nodes/{node}/disks/directory

PVE Managed Directory storages.

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
      "device": {
        "description": "The mounted device.",
        "type": "string"
      },
      "options": {
        "description": "The mount options.",
        "type": "string"
      },
      "path": {
        "description": "The mount path.",
        "type": "string"
      },
      "type": {
        "description": "The filesystem type.",
        "type": "string"
      },
      "unitfile": {
        "description": "The path of the mount unit.",
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
  "description": "PVE Managed Directory storages.",
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
        "device": {
          "description": "The mounted device.",
          "type": "string"
        },
        "options": {
          "description": "The mount options.",
          "type": "string"
        },
        "path": {
          "description": "The mount path.",
          "type": "string"
        },
        "type": {
          "description": "The filesystem type.",
          "type": "string"
        },
        "unitfile": {
          "description": "The path of the mount unit.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
