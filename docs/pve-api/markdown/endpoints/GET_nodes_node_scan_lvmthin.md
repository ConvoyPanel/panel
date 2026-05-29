# GET /nodes/{node}/scan/lvmthin

List local LVM Thin Pools.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| vg | string | yes |  |

## Returns

```json
{
  "items": {
    "properties": {
      "lv": {
        "description": "The LVM Thin Pool name (LVM logical volume).",
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
    "/storage",
    [
      "Datastore.Allocate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List local LVM Thin Pools.",
  "method": "GET",
  "name": "lvmthinscan",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "vg": {
        "maxLength": 100,
        "pattern": "[a-zA-Z0-9\\.\\+\\_][a-zA-Z0-9\\.\\+\\_\\-]+",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/storage",
      [
        "Datastore.Allocate"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "lv": {
          "description": "The LVM Thin Pool name (LVM logical volume).",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
